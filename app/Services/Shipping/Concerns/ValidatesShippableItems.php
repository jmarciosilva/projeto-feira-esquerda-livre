<?php

namespace App\Services\Shipping\Concerns;

use App\DTO\ShippingQuoteData;
use App\Enums\ItemType;
use App\Models\Expositor;
use App\Models\Product;
use App\Models\ProductOffer;
use Illuminate\Support\Collection;

/**
 * Validações e orquestração de frete compartilhadas entre os provedores
 * (Melhor Envio, Frenet, ...). Cada serviço só implementa a chamada HTTP
 * em si (calculate()) e o mapeamento da resposta.
 */
trait ValidatesShippableItems
{
    abstract public function isConfigured(): bool;

    /**
     * O que se despacha é uma oferta, não um item de catálogo: peso, dimensões
     * e valor segurado são de quem embala e envia. Desde a CAT-DOM-01 esses
     * dados vivem em `product_offers`.
     *
     * @param  Collection<int, mixed>  $items
     * @param  callable(string, string, array<int, array{offer: ProductOffer, quantity: int}>): array<int, ShippingQuoteData>  $calculate
     * @return array<int, ShippingQuoteData>
     */
    protected function quoteForStoreUsing(
        Expositor $store,
        string $destinationZipcode,
        Collection $items,
        string $providerLabel,
        callable $calculate,
    ): array {
        $originError = $this->originAddressError($store, $providerLabel);

        if ($originError) {
            return [ShippingQuoteData::error($originError)];
        }

        $products = [];

        foreach ($items as $item) {
            $offer = $item->offer;

            if (! $offer instanceof ProductOffer || ! $this->isShippable($offer->product)) {
                continue;
            }

            $logisticError = $this->logisticDataError($offer);

            if ($logisticError) {
                return [ShippingQuoteData::error($logisticError)];
            }

            $products[] = [
                'offer' => $offer,
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

        return $calculate((string) $store->zipcode, $destinationZipcode, $products);
    }

    protected function originAddressError(Expositor $store, string $providerLabel): ?string
    {
        if (! $this->isConfigured()) {
            return "O {$providerLabel} ainda não está configurado para calcular frete.";
        }

        if (blank($store->zipcode)) {
            return "A loja {$store->name} ainda não possui CEP de origem cadastrado.";
        }

        return null;
    }

    protected function logisticDataError(ProductOffer $offer): ?string
    {
        $missing = collect([
            'peso' => $offer->weight,
            'altura' => $offer->height,
            'largura' => $offer->width,
            'comprimento' => $offer->length,
        ])->filter(fn ($value) => blank($value) || (float) $value <= 0)->keys()->implode(', ');

        if ($missing === '') {
            return null;
        }

        return "O produto {$offer->product->name} não possui dados logísticos cadastrados: {$missing}.";
    }

    protected function isShippable(?Product $product): bool
    {
        return $product?->item_type === ItemType::Produto;
    }

    protected function onlyDigits(string $value): string
    {
        return preg_replace('/\D+/', '', $value) ?? '';
    }
}
