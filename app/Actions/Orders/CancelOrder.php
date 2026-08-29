<?php

namespace App\Actions\Orders;

use App\Actions\Orders\Concerns\TransicionaPedido;
use App\Actions\Stock\ReleaseOrderStock;
use App\Enums\OrderStatus;
use App\Exceptions\TransicaoDePedidoInvalida;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

/**
 * Encerra a intenção de compra de um pedido que ainda não foi pago.
 *
 * ## Por que a liberação mora dentro desta transação
 *
 * A FIN-SEC-01E entregou `ReleaseOrderStock` pronta e sem chamador, dizendo que
 * decidir *quando* liberar era ciclo de vida do pedido. É aqui. E precisa ser
 * na mesma transação: cancelar num commit e devolver o estoque em outro abre
 * uma janela em que o pedido já não existe comercialmente mas as unidades
 * continuam presas — exatamente o vazamento V-1.
 *
 * ## Só o que ainda não foi pago
 *
 * Cancelar é encerrar uma intenção. Desfazer uma venda paga é reversão
 * financeira, tem outro estado (`Estornado`) e outra action, na 01F-D. A matriz
 * de `OrderStatus` recusa a diferença por conta própria, e esta action não
 * abre exceção para ela.
 */
final class CancelOrder
{
    use TransicionaPedido;

    /**
     * @throws TransicaoDePedidoInvalida quando o pedido não está mais cancelável
     */
    public function __invoke(Order $order): Order
    {
        return DB::transaction(function () use ($order) {
            $atual = $this->travarPedido($order);

            // Já cancelado: a operação é idempotente e não devolve estoque
            // duas vezes nem remarca timestamp. Cancelar o que já está
            // cancelado é o resultado que se queria.
            if ($atual->status === OrderStatus::Cancelado) {
                return $atual;
            }

            $this->exigirTransicao($atual, OrderStatus::Cancelado);

            // `ReleaseOrderStock` é idempotente por conta própria: recusa
            // pedido sem reserva, já liberado ou já consumido.
            app(ReleaseOrderStock::class)($atual);

            $atual->forceFill(['status' => OrderStatus::Cancelado])->save();

            return $atual->refresh();
        });
    }
}
