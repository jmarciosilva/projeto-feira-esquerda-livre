<?php

namespace App\Actions\Orders\Concerns;

use App\Enums\OrderStatus;
use App\Exceptions\TransicaoDePedidoInvalida;
use App\Models\Order;

/**
 * O que toda transição de pedido faz antes de escrever qualquer coisa.
 *
 * A ordem de travamento é sempre a mesma em toda a trilha FIN-SEC:
 *
 *     Order  →  ProductOffers em id crescente
 *
 * `ConfirmOrderPayment` já segue essa ordem, e nenhuma action nova pode
 * invertê-la: duas transações que travem os mesmos recursos em ordens opostas
 * fecham um ciclo, e o banco resolve matando uma delas.
 */
trait TransicionaPedido
{
    /**
     * Relê o pedido sob lock — o estado que importa é o do banco, não o que a
     * superfície carregava na memória quando o usuário clicou.
     */
    protected function travarPedido(Order $order): Order
    {
        return Order::query()
            ->whereKey($order->getKey())
            ->lockForUpdate()
            ->firstOrFail();
    }

    /**
     * @throws TransicaoDePedidoInvalida
     */
    protected function exigirTransicao(Order $order, OrderStatus $destino): void
    {
        if (! $order->status->podeIrPara($destino)) {
            throw new TransicaoDePedidoInvalida($order->status, $destino);
        }
    }
}
