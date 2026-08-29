<?php

namespace App\Actions\Stock\Concerns;

use App\Models\Order;
use App\Models\ProductOffer;
use Illuminate\Support\Collection;

/**
 * O que as três operações de estoque — reservar, consumir e liberar — precisam
 * saber antes de tocar em qualquer número.
 */
trait OperaEstoqueDoPedido
{
    /**
     * Quanto cada oferta controlada foi comprometida por este pedido.
     *
     * Nem todo item participa do estoque. Ficam de fora:
     *
     * - **itens digitais**, que não têm unidade física a disputar — um curso
     *   vendido dez vezes continua sendo o mesmo curso;
     * - ofertas que não controlam estoque (`has_stock` falso) ou que não
     *   informam quantidade (`stock_quantity` nulo): as duas formas de dizer
     *   "ilimitado" que o cadastro já oferecia antes desta fase;
     * - itens cuja oferta não existe mais — não há o que travar nem devolver.
     *
     * @return Collection<int, int> quantidade por `product_offer_id`
     */
    protected function comprometidoPorOferta(Order $order): Collection
    {
        return $order->items()
            ->whereNotNull('product_offer_id')
            ->with('offer.product')
            ->get()
            ->filter(fn ($item) => $this->controlaEstoque($item->offer))
            ->groupBy('product_offer_id')
            ->map(fn (Collection $itens) => (int) $itens->sum('quantity'));
    }

    protected function controlaEstoque(?ProductOffer $offer): bool
    {
        return $offer !== null
            && ! $offer->product?->is_digital
            && $offer->has_stock
            && $offer->stock_quantity !== null;
    }

    /**
     * Trava as ofertas para escrita, sempre na mesma ordem.
     *
     * A ordenação por id não é estética: dois pedidos que travam as mesmas duas
     * ofertas em ordens opostas se bloqueiam em círculo, e o banco resolve isso
     * matando uma das transações por deadlock. Subindo sempre por `id`, a
     * segunda transação apenas espera.
     *
     * @param  Collection<int, int>  $porOferta
     * @return Collection<int, ProductOffer> indexada por id
     */
    protected function travarOfertas(Collection $porOferta): Collection
    {
        if ($porOferta->isEmpty()) {
            return collect();
        }

        return ProductOffer::query()
            ->whereIn('id', $porOferta->keys())
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');
    }

    protected function nomeDoItem(ProductOffer $offer): string
    {
        return $offer->product?->name ?? 'Item';
    }
}
