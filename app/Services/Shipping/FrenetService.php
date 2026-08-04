<?php

namespace App\Services\Shipping;

use App\DTO\ShippingQuoteData;
use App\Models\Expositor;
use App\Models\Product;
use App\Models\SiteSetting;
use App\Services\Shipping\Concerns\ValidatesShippableItems;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Throwable;

class FrenetService
{
    use ValidatesShippableItems;

    private const BASE_URL = 'https://api.frenet.com.br';

    /**
     * @param  array<int, array{product: Product, quantity: int}>  $products
     * @return array<int, ShippingQuoteData>
     */
    public function calculate(string $originZipcode, string $destinationZipcode, array $products): array
    {
        $payloadItems = $this->productsPayload($products);

        if ($payloadItems === []) {
            return [
                ShippingQuoteData::error('Nenhum produto físico com dados logísticos foi informado para cálculo de frete.'),
            ];
        }

        $invoiceValue = collect($products)->sum(
            fn (array $item) => round((float) ($item['product']->price ?? 0), 2) * max(1, (int) $item['quantity'])
        );

        $payload = [
            'SellerCEP' => $this->onlyDigits($originZipcode),
            'RecipientCEP' => $this->onlyDigits($destinationZipcode),
            'ShipmentInvoiceValue' => round($invoiceValue, 2),
            'ShippingItemArray' => $payloadItems,
        ];

        try {
            $response = Http::baseUrl(self::BASE_URL)
                ->withHeaders(['token' => $this->token()])
                ->acceptJson()
                ->asJson()
                ->timeout($this->timeout())
                ->post('/shipping/quote', $payload)
                ->throw();

            $quotes = $response->json('ShippingSevicesArray');

            if (! is_array($quotes)) {
                return [ShippingQuoteData::error('A resposta da Frenet veio em um formato inesperado.')];
            }

            return collect($quotes)
                ->filter(fn ($quote) => is_array($quote))
                ->map(fn (array $quote) => ShippingQuoteData::fromFrenet($quote))
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
        return $this->quoteForStoreUsing($store, $destinationZipcode, $items, 'Frenet', $this->calculate(...));
    }

    public function isConfigured(): bool
    {
        return filled($this->token());
    }

    private function token(): ?string
    {
        $settings = SiteSetting::instance();

        return $settings->frenet_ativo && filled($settings->frenet_token)
            ? $settings->frenet_token
            : config('frenet.token');
    }

    private function timeout(): int
    {
        return max(5, (int) config('frenet.timeout', 20));
    }

    /**
     * @param  array<int, array{product: Product, quantity: int}>  $products
     * @return array<int, array<string, mixed>>
     */
    private function productsPayload(array $products): array
    {
        return collect($products)
            ->map(fn (array $item) => [
                'Weight' => (float) $item['product']->weight,
                'Length' => (float) $item['product']->length,
                'Height' => (float) $item['product']->height,
                'Width' => (float) $item['product']->width,
                'Quantity' => max(1, (int) $item['quantity']),
            ])
            ->values()
            ->all();
    }

    private function apiErrorMessage(RequestException $exception): string
    {
        $response = $exception->response;
        $message = $response?->json('Msg') ?: $response?->json('message');

        if (is_string($message) && $message !== '') {
            return "Frenet: {$message}";
        }

        if ($response?->status() === 401) {
            return 'Frenet: token inválido ou ausente.';
        }

        return 'Frenet: não foi possível calcular o frete com os dados informados.';
    }
}
