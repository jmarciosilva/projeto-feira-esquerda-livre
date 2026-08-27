<?php

namespace Database\Seeders\Concerns;

use App\Actions\Catalog\SaveProductWithOffer;
use App\Models\Product;
use App\Models\ProductOffer;
use Illuminate\Support\Arr;

/**
 * Mantém a oferta de um item semeado alinhada com o produto.
 *
 * Os seeders continuam escrevendo `products` como sempre — são idempotentes por
 * `name` ou `slug`, e trocá-los por `SaveProductWithOffer` custaria essa
 * idempotência sem ganho. O que falta é a contrapartida da CAT-DOM-01: todo
 * item semeado precisa da oferta de quem o oferece, senão ele não aparece em
 * vitrine nenhuma.
 */
trait SincronizaOfertaDoItem
{
    protected function sincronizarOferta(Product $product, int $expositorId): ProductOffer
    {
        return ProductOffer::updateOrCreate(
            ['product_id' => $product->id, 'expositor_id' => $expositorId],
            Arr::only($product->getAttributes(), SaveProductWithOffer::CAMPOS_DA_OFERTA),
        );
    }
}
