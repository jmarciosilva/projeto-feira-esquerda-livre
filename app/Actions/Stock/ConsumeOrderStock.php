<?php

namespace App\Actions\Stock;

use App\Actions\Stock\Concerns\OperaEstoqueDoPedido;
use App\Exceptions\EstoqueInsuficiente;
use App\Models\Order;
use App\Models\ProductOffer;
use Illuminate\Support\Collection;

/**
 * Baixa definitivamente as unidades de um pedido pago.
 *
 * **Roda dentro da transação que confirma o pagamento.** Se a baixa falhar, o
 * pedido não é confirmado: estoque é consistência transacional, não efeito
 * eventual de listener.
 *
 * ## Dois caminhos, por causa dos pedidos anteriores a esta fase
 *
 * Um pedido criado depois da FIN-SEC-01E chega aqui com reserva feita, e o
 * consumo apenas converte o que já era dele: o físico cai e o comprometido é
 * devolvido junto, porque deixou de ser promessa e virou saída.
 *
 * Um pedido criado antes não reservou nada — e não pode ser tratado como se
 * tivesse. Ele **disputa** o estoque que existir no momento do pagamento, como
 * qualquer outro. Se não houver, a confirmação falha fechada: é preferível um
 * pagamento recebido que precisa de tratamento humano a um pedido dado como
 * pago que ninguém consegue atender.
 */
final class ConsumeOrderStock
{
    use OperaEstoqueDoPedido;

    /**
     * @throws EstoqueInsuficiente quando um pedido sem reserva não encontra
     *                             estoque disponível no momento do pagamento
     */
    public function __invoke(Order $order): void
    {
        if ($order->stock_consumed_at !== null) {
            return;
        }

        $porOferta = $this->comprometidoPorOferta($order);

        if ($porOferta->isEmpty()) {
            $order->forceFill(['stock_consumed_at' => now()])->save();

            return;
        }

        $ofertas = $this->travarOfertas($porOferta);
        $temReserva = $order->stock_reserved_at !== null && $order->stock_released_at === null;

        if (! $temReserva) {
            $this->exigirDisponibilidade($porOferta, $ofertas);
        }

        foreach ($porOferta as $ofertaId => $quantidade) {
            $oferta = $ofertas[$ofertaId] ?? null;

            if ($oferta === null || ! $this->controlaEstoque($oferta)) {
                continue;
            }

            $oferta->decrement('stock_quantity', $quantidade);

            if ($temReserva) {
                // A promessa virou saída: o comprometido cai junto com o físico.
                $oferta->decrement('reserved_quantity', min($quantidade, (int) $oferta->reserved_quantity));
            }
        }

        $order->forceFill(['stock_consumed_at' => now()])->save();
    }

    /**
     * @param  Collection<int, int>  $porOferta
     * @param  Collection<int, ProductOffer>  $ofertas
     *
     * @throws EstoqueInsuficiente
     */
    private function exigirDisponibilidade($porOferta, $ofertas): void
    {
        foreach ($porOferta as $ofertaId => $quantidade) {
            $oferta = $ofertas[$ofertaId] ?? null;

            if ($oferta === null || ! $this->controlaEstoque($oferta)) {
                continue;
            }

            $disponivel = (int) $oferta->stock_quantity - (int) $oferta->reserved_quantity;

            if ($quantidade > $disponivel) {
                throw new EstoqueInsuficiente(
                    $this->nomeDoItem($oferta),
                    $quantidade,
                    max($disponivel, 0),
                );
            }
        }
    }
}
