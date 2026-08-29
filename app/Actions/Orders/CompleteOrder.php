<?php

namespace App\Actions\Orders;

use App\Actions\Orders\Concerns\TransicionaPedido;
use App\Enums\OrderStatus;
use App\Exceptions\TransicaoDePedidoInvalida;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

/**
 * Encerra o pedido cuja operação comercial e logística terminou bem.
 *
 * Existe por causa do V-7: `TrackShipmentsJob` escrevia `Concluido` direto,
 * checando apenas se o pedido já não estava concluído. Um pedido cancelado
 * cujo envio seguia em trânsito e era entregue voltava à vida como concluído —
 * um job de logística ressuscitando um estado terminal do domínio financeiro.
 *
 * A condição logística continua sendo do job, que sabe olhar as entregas. O que
 * o job não sabe — e não deve saber — é se o pedido ainda pode ser concluído.
 * Essa pergunta é da matriz de `OrderStatus`.
 */
final class CompleteOrder
{
    use TransicionaPedido;

    /**
     * @throws TransicaoDePedidoInvalida quando o pedido não pode mais concluir
     */
    public function __invoke(Order $order): Order
    {
        return DB::transaction(function () use ($order) {
            $atual = $this->travarPedido($order);

            if ($atual->status === OrderStatus::Concluido) {
                return $atual;
            }

            $this->exigirTransicao($atual, OrderStatus::Concluido);

            $atual->forceFill(['status' => OrderStatus::Concluido])->save();

            return $atual->refresh();
        });
    }
}
