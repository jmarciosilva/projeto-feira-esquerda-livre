<?php

namespace App\Services\Shipping;

use App\DTO\ShippingQuoteData;
use App\Enums\ItemType;
use App\Models\Expositor;
use App\Models\Product;
use App\Models\SiteSetting;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Throwable;

class MelhorEnvioService
{
    /**
     * @param  array<int, array{product: Product, quantity: int}>  $products
     * @return array<int, ShippingQuoteData>
     */
    public function calculate(string $originZipcode, string $destinationZipcode, array $products): array
    {
        $payloadProducts = $this->productsPayload($products);

        if ($payloadProducts === []) {
            return [
                ShippingQuoteData::error('Nenhum produto físico com dados logísticos foi informado para cálculo de frete.'),
            ];
        }

        $payload = [
            'from' => ['postal_code' => $this->onlyDigits($originZipcode)],
            'to' => ['postal_code' => $this->onlyDigits($destinationZipcode)],
            'products' => $payloadProducts,
        ];

        try {
            $response = Http::baseUrl($this->baseUrl())
                ->withToken($this->token())
                ->acceptJson()
                ->asJson()
                ->timeout($this->timeout())
                ->post('/api/v2/me/shipment/calculate', $payload)
                ->throw();

            $quotes = $response->json();

            if (! is_array($quotes)) {
                return [ShippingQuoteData::error('A resposta do Melhor Envio veio em um formato inesperado.')];
            }

            return collect($quotes)
                ->filter(fn ($quote) => is_array($quote))
                ->map(fn (array $quote) => ShippingQuoteData::fromMelhorEnvio($quote))
                ->values()
                ->all();
        } catch (RequestException $exception) {
            report($exception);

            return [ShippingQuoteData::error($this->apiErrorMessage($exception))];
        } catch (Throwable $exception) {
            report($exception);

            return [ShippingQuoteData::error('Não foi possível consultar o frete agora. Tente novamente em alguns instantes.')];
        }
    }

    /**
     * @param  Collection<int, mixed>  $items
     * @return array<int, ShippingQuoteData>
     */
    public function quoteForStore(Expositor $store, string $destinationZipcode, Collection $items): array
    {
        $originError = $this->originAddressError($store);

        if ($originError) {
            return [ShippingQuoteData::error($originError)];
        }

        $products = [];

        foreach ($items as $item) {
            $product = $item->product;

            if (! $product instanceof Product || ! $this->isShippable($product)) {
                continue;
            }

            $logisticError = $this->logisticDataError($product);

            if ($logisticError) {
                return [ShippingQuoteData::error($logisticError)];
            }

            $products[] = [
                'product' => $product,
                'quantity' => (int) $item->quantity,
            ];
        }

        if ($products === []) {
            return [
                new ShippingQuoteData(
                    service_id: 'sem-frete',
                    company: 'Feira Esquerda Livre',
                    service_name: 'Sem frete para esta loja',
                    price: 0.0,
                    delivery_time: null,
                ),
            ];
        }

        return $this->calculate((string) $store->zipcode, $destinationZipcode, $products);
    }

    /**
     * Consulta eventos de rastreio de um código no Melhor Envio.
     *
     * @return array<int, array{status: string, description: string, location: ?string, happened_at: string}>
     */
    public function track(string $trackingCode): array
    {
        if (! $this->isConfigured() || blank($trackingCode)) {
            return [];
        }

        try {
            $response = Http::baseUrl($this->baseUrl())
                ->withToken($this->token())
                ->acceptJson()
                ->timeout($this->timeout())
                ->get('/api/v2/me/shipper/orders/tracking', ['q' => $trackingCode])
                ->throw();

            $data = $response->json();

            if (! is_array($data)) {
                return [];
            }

            return $this->normalizeTrackingEvents($data);
        } catch (Throwable $exception) {
            report($exception);

            return [];
        }
    }

    /**
     * @param  array<mixed>  $data
     * @return array<int, array{status: string, description: string, location: ?string, happened_at: string}>
     */
    private function normalizeTrackingEvents(array $data): array
    {
        $events = $data['tracking'] ?? $data['events'] ?? $data;

        if (! is_array($events)) {
            return [];
        }

        return collect($events)
            ->filter(fn ($e) => is_array($e))
            ->map(fn (array $e) => [
                'status'      => (string) ($e['status'] ?? 'in_transit'),
                'description' => (string) ($e['message'] ?? $e['description'] ?? 'Atualização de status'),
                'location'    => isset($e['city'], $e['state']) ? "{$e['city']}, {$e['state']}" : null,
                'happened_at' => $e['event_date'] ?? $e['created_at'] ?? now()->toDateTimeString(),
            ])
            ->values()
            ->all();
    }

    public function isConfigured(): bool
    {
        return filled($this->token()) && filled($this->baseUrl());
    }

    /** URL base da API/OAuth do Melhor Envio (sandbox ou produção) conforme configuração do admin. */
    public function baseUrl(): string
    {
        $settings = SiteSetting::instance();

        // Assim que houver Client ID cadastrado, o checkbox de sandbox manda — mesmo
        // antes da integração estar "ativa" (necessário durante o próprio handshake OAuth).
        if ($settings->melhor_envio_ativo || filled($settings->melhor_envio_client_id)) {
            return $settings->melhor_envio_sandbox === false
                ? 'https://www.melhorenvio.com.br'
                : 'https://sandbox.melhorenvio.com.br';
        }

        return rtrim((string) config('melhorenvio.base_url'), '/');
    }

    private function token(): ?string
    {
        $settings = SiteSetting::instance();

        if (! $settings->melhor_envio_ativo || blank($settings->melhor_envio_token)) {
            return config('melhorenvio.token');
        }

        $this->refreshTokenIfExpiring($settings);

        return $settings->melhor_envio_token;
    }

    /** Renova o access_token via refresh_token quando estiver perto de expirar. */
    private function refreshTokenIfExpiring(SiteSetting $settings): void
    {
        if (blank($settings->melhor_envio_refresh_token) || blank($settings->melhor_envio_token_expires_at)) {
            return;
        }

        if (now()->lt($settings->melhor_envio_token_expires_at->subMinutes(5))) {
            return;
        }

        try {
            $response = Http::baseUrl($this->baseUrl())
                ->asJson()
                ->acceptJson()
                ->timeout($this->timeout())
                ->post('/oauth/token', [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $settings->melhor_envio_refresh_token,
                    'client_id' => $settings->melhor_envio_client_id,
                    'client_secret' => $settings->melhor_envio_client_secret,
                ])
                ->throw();

            $data = $response->json();

            $settings->update([
                'melhor_envio_token' => $data['access_token'] ?? $settings->melhor_envio_token,
                'melhor_envio_refresh_token' => $data['refresh_token'] ?? $settings->melhor_envio_refresh_token,
                'melhor_envio_token_expires_at' => now()->addSeconds((int) ($data['expires_in'] ?? 2592000)),
            ]);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private function timeout(): int
    {
        return max(5, (int) config('melhorenvio.timeout', 20));
    }

    /**
     * @param  array<int, array{product: Product, quantity: int}>  $products
     * @return array<int, array<string, mixed>>
     */
    private function productsPayload(array $products): array
    {
        return collect($products)
            ->map(function (array $item) {
                $product = $item['product'];

                return [
                    'id' => (string) $product->id,
                    'width' => (float) $product->width,
                    'height' => (float) $product->height,
                    'length' => (float) $product->length,
                    'weight' => (float) $product->weight,
                    'insurance_value' => round((float) ($product->price ?? 0), 2),
                    'quantity' => max(1, (int) $item['quantity']),
                ];
            })
            ->values()
            ->all();
    }

    private function originAddressError(Expositor $store): ?string
    {
        if (! $this->isConfigured()) {
            return 'O Melhor Envio ainda não está configurado para calcular frete.';
        }

        if (blank($store->zipcode)) {
            return "A loja {$store->name} ainda não possui CEP de origem cadastrado.";
        }

        return null;
    }

    private function logisticDataError(Product $product): ?string
    {
        $missing = collect([
            'peso' => $product->weight,
            'altura' => $product->height,
            'largura' => $product->width,
            'comprimento' => $product->length,
        ])->filter(fn ($value) => blank($value) || (float) $value <= 0)->keys()->implode(', ');

        if ($missing === '') {
            return null;
        }

        return "O produto {$product->name} não possui dados logísticos cadastrados: {$missing}.";
    }

    private function isShippable(Product $product): bool
    {
        return $product->item_type === ItemType::Produto;
    }

    private function onlyDigits(string $value): string
    {
        return preg_replace('/\D+/', '', $value) ?? '';
    }

    private function apiErrorMessage(RequestException $exception): string
    {
        $response = $exception->response;
        $message = $response?->json('message') ?: $response?->json('error');

        if (is_string($message) && $message !== '') {
            return "Melhor Envio: {$message}";
        }

        if ($response?->status() === 401) {
            return 'Melhor Envio: token inválido, ausente ou expirado.';
        }

        return 'Melhor Envio: não foi possível calcular o frete com os dados informados.';
    }
}
