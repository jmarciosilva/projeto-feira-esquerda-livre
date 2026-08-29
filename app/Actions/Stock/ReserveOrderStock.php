<?php

namespace App\Actions\Stock;

use App\Actions\Stock\Concerns\OperaEstoqueDoPedido;
use App\Exceptions\EstoqueInsuficiente;
use App\Models\Order;

/**
 * Compromete as unidades de um pedido recém-criado.
 *
 * Reservar não é diminuir o estoque: o físico continua igual, e o que muda é
 * quanto dele já tem dono. A diferença importa com Pix, onde o pedido fica
 * pendente por minutos — sem reserva, um segundo cliente compraria a mesma
 * unidade nesse intervalo, e foi exatamente o que a auditoria reproduziu.
 *
 * **Roda dentro da transação que cria o pedido.** Reservar depois do commit
 * abriria de novo a janela que esta ação existe para fechar.
 */
final class ReserveOrderStock
{
    use OperaEstoqueDoPedido;

    /**
     * @throws EstoqueInsuficiente quando qualquer item não couber no disponível
     */
    public function __invoke(Order $order): void
    {
        // Reserva já feita: a operação é idempotente e não comprometeria as
        // mesmas unidades duas vezes.
        if ($order->stock_reserved_at !== null) {
            return;
        }

        $porOferta = $this->comprometidoPorOferta($order);

        if ($porOferta->isEmpty()) {
            return;
        }

        $ofertas = $this->travarOfertas($porOferta);

        // Valida tudo antes de escrever qualquer coisa: um pedido em que o
        // segundo item não cabe não pode deixar o primeiro comprometido.
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

        foreach ($porOferta as $ofertaId => $quantidade) {
            $ofertas[$ofertaId]?->increment('reserved_quantity', $quantidade);
        }

        $order->forceFill(['stock_reserved_at' => now()])->save();
    }
}
