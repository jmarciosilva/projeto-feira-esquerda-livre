<?php

namespace App\Actions\Orders;

use App\Actions\Orders\Concerns\TransicionaPedido;
use App\Actions\Stock\ReleaseOrderStock;
use App\Enums\OrderStatus;
use App\Exceptions\TransicaoDePedidoInvalida;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

/**
 * Encerra a intenção de pagamento que perdeu validade temporal.
 *
 * ## Por que expirar não é cancelar
 *
 * Cancelar é decisão de alguém; expirar é o relógio. A FIN-SEC-01F-B separou os
 * dois estados justamente porque colapsá-los apagava a informação de por que o
 * pedido morreu — e quem lê um pedido meses depois precisa saber se o cliente
 * desistiu ou se o Pix simplesmente venceu.
 *
 * ## A liberação vive dentro desta transação
 *
 * Expirar sem devolver o estoque, ou devolver em outro commit, abriria a janela
 * em que o pedido já não existe comercialmente mas as unidades seguem presas.
 * Depois do commit não pode ser observável um pedido `Expirado` com
 * `reserved_quantity` ainda comprometido por ele.
 *
 * ## Prazo é evidência, não estimativa
 *
 * Sem `payment_expires_at`, nada acontece. `NULL` significa que a aplicação não
 * tem como saber quando aquela intenção venceu, e supor uma idade máxima
 * expiraria pedidos que ninguém autorizou expirar.
 */
final class ExpireOrder
{
    use TransicionaPedido;

    /**
     * @throws TransicaoDePedidoInvalida quando o pedido não está mais expirável
     */
    public function __invoke(Order $order): Order
    {
        return DB::transaction(function () use ($order) {
            $atual = $this->travarPedido($order);

            // Já expirado: idempotente. Não devolve estoque de novo, não
            // remarca `stock_released_at`, não mexe no prazo.
            if ($atual->status === OrderStatus::Expirado) {
                return $atual;
            }

            // A corrida com o pagamento se resolve aqui: se a confirmação
            // chegou primeiro, o pedido relido sob lock já não é pendente, e a
            // matriz recusa. Estoque consumido nunca é liberado depois.
            $this->exigirTransicao($atual, OrderStatus::Expirado);

            if (! $this->prazoVencido($atual)) {
                return $atual;
            }

            app(ReleaseOrderStock::class)($atual);

            $atual->forceFill(['status' => OrderStatus::Expirado])->save();

            return $atual->refresh();
        });
    }

    /**
     * Qual prazo manda, e se ele já passou.
     *
     * Dois relógios governam um pedido pendente, em estágios diferentes:
     *
     * - **`payment_expires_at`** — prazo objetivo da intenção de pagamento que
     *   o gateway criou. Quando existe, é ele que vale: a plataforma não tem
     *   autoridade para encerrar antes do prazo que o próprio gateway deu, e um
     *   Pix com uma hora de validade não pode morrer aos trinta minutos porque
     *   a janela interna venceu.
     *
     * - **`checkout_expires_at`** — janela interna, que governa apenas o
     *   intervalo em que ainda **não existe** intenção nenhuma. É o caso do
     *   cliente que cria o pedido, vê a tela de pagamento e vai embora.
     *
     * As duas nulas significa pedido legado ou manual: sem evidência de prazo,
     * nada expira. `NULL` continua sendo "não sei", nunca "venceu".
     */
    private function prazoVencido(Order $order): bool
    {
        $prazo = $order->payment_expires_at ?? $order->checkout_expires_at;

        return $prazo !== null && ! $prazo->isFuture();
    }
}
