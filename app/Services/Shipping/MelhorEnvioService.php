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

    public function isConfigured(): bool
    {
        return filled($this->token()) && filled($this->baseUrl());
    }

    private function baseUrl(): string
    {
        $settings = SiteSetting::instance();

        if ($settings->melhor_envio_ativo && $settings->melhor_envio_sandbox === false) {
            return 'https://www.melhorenvio.com.br';
        }

        return rtrim((string) config('melhorenvio.base_url'), '/');
    }

    private function token(): ?string
    {
        $settings = SiteSetting::instance();

        return $settings->melhor_envio_ativo && filled($settings->melhor_envio_token)
            ? $settings->melhor_envio_token
            : config('melhorenvio.token');
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
