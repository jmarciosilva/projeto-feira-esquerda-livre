<?php

namespace App\Actions\Catalog;

use App\Exceptions\OfertaComReservaAtiva;
use App\Models\ProductOffer;
use Illuminate\Support\Facades\DB;

/**
 * Tira a oferta da loja — se ela não dever mais nada a ninguém.
 *
 * ## Por que a exclusão precisa de transação
 *
 * Ler `reserved_quantity` e depois apagar são dois momentos, e entre eles cabe
 * um checkout inteiro:
 *
 *     T1 lê reserved = 0
 *     T2 cria o pedido e reserva 1
 *     T1 apaga a oferta
 *
 * O resultado seria um pedido com reserva apontando para uma oferta que não
 * existe mais — exatamente o estado que a FIN-SEC-01E existe para impedir. Por
 * isso a leitura acontece **sob `lockForUpdate`**, dentro da mesma transação
 * que apaga: o checkout concorrente ou espera e encontra a linha já apagada, ou
 * chega antes e faz a exclusão ser recusada.
 *
 * A trava é de uma linha só. Não há ciclo possível com a ordem crescente de id
 * que `OperaEstoqueDoPedido::travarOfertas()` usa: quem trava um recurso apenas
 * espera, nunca fecha um círculo.
 *
 * Apagar a **oferta** continua não apagando o **produto**: é a distinção que a
 * CAT-DOM-01 introduziu, e ela não muda aqui.
 */
final class DeleteProductOffer
{
    /**
     * @throws OfertaComReservaAtiva quando ainda há unidades comprometidas
     */
    public function __invoke(ProductOffer $offer): void
    {
        DB::transaction(function () use ($offer) {
            $atual = ProductOffer::query()
                ->whereKey($offer->getKey())
                ->lockForUpdate()
                ->first();

            // Já apagada por outro caminho: o resultado desejado é o que vale.
            if ($atual === null) {
                return;
            }

            if ((int) $atual->reserved_quantity > 0) {
                throw new OfertaComReservaAtiva(
                    $atual->product?->name ?? 'Esta oferta',
                    (int) $atual->reserved_quantity,
                );
            }

            $atual->delete();
        });
    }
}
