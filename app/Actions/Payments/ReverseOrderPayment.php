<?php

namespace App\Actions\Payments;

use App\Actions\Orders\Concerns\TransicionaPedido;
use App\Enums\OrderSplitStatus;
use App\Enums\OrderStatus;
use App\Exceptions\TransicaoDePedidoInvalida;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

/**
 * A transição de domínio "o dinheiro deste pedido voltou".
 *
 * Espelha `ConfirmOrderPayment`: uma operação só, atômica, que não conhece
 * gateway nenhum. Quem integra afirma que houve reversão e prova a correlação;
 * quem decide o que isso faz com o pedido é esta ação.
 *
 * ## Reversão financeira não é devolução física (D-FIN-31)
 *
 * Esta ação **não** repõe `stock_quantity`, e a omissão é a regra, não um
 * esquecimento. O caminho comum de um estorno é este:
 *
 *     pago → estoque consumido → produto enviado → cliente recebe → refund
 *
 * O dinheiro volta; o produto continua com o cliente. Repor estoque aqui criaria
 * unidades que não existem na prateleira, e a próxima pessoa a comprar levaria
 * um pedido que ninguém consegue atender — que é o mesmo dano que a FIN-SEC-01E
 * passou a impedir pelo outro lado.
 *
 * Repor exige evidência de retorno logístico. `order_shippings` tem
 * `ShippingStatus::Returned`, mas nada no domínio hoje liga uma devolução física
 * a uma reversão financeira, e inventar esse vínculo aqui seria pior do que não
 * ter: `stock_consumed_at` permanece marcado, dizendo a verdade — as unidades
 * saíram.
 *
 * ## O que é preservado, e por quê
 *
 * | Campo | Continua | Porque responde |
 * |---|---|---|
 * | `paid_at` | intacto | "quando foi pago?" — segue verdadeiro (D-FIN-32) |
 * | `mercado_pago_payment_id` | intacto | identidade do pagamento, não do estorno (D-FIN-35) |
 * | `stock_consumed_at` | intacto | as unidades saíram mesmo (D-FIN-31) |
 * | `payment_payload['payment']` | intacto | evidência do pagamento original |
 * | `splits[].confirmed_at` | intacto | "quando o repasse passou a ser devido?" |
 *
 * O que a reversão acrescenta é `reversed_at`, e não substitui nada.
 *
 * ## Lock hierarchy
 *
 * `Order → dependentes`, como toda a trilha. Nenhuma `ProductOffer` é travada
 * aqui, e não é economia: como não há mutação de estoque, travar a oferta só
 * criaria contenção com o checkout de outra pessoa sem proteger coisa alguma.
 *
 * ## Sem chamada externa
 *
 * Esta ação não pede estorno a gateway nenhum. Ela **registra** um estorno que
 * já aconteceu lá fora. Inverter isso — a ação de domínio disparando dinheiro de
 * volta — faria toda reentrega de webhook virar um novo estorno.
 */
final class ReverseOrderPayment
{
    use TransicionaPedido;

    /**
     * @param  string  $motivo  natureza da reversão segundo o gateway
     *                          ('refunded', 'charged_back'), gravada em
     *                          `payment_status`
     * @return Order o pedido no estado final — revertido agora, ou já revertido
     *
     * @throws TransicaoDePedidoInvalida quando o pedido nunca foi pago, ou já
     *                                   foi encerrado por outro caminho
     */
    public function __invoke(Order $order, string $motivo = 'refunded'): Order
    {
        return DB::transaction(function () use ($order, $motivo) {
            $atual = $this->travarPedido($order);

            // Já revertido: refund reentregue é a mesma reversão chegando de
            // novo. Devolver o estado atual é a resposta idempotente — e é o
            // que impede a segunda entrega de remarcar `reversed_at`, reverter
            // splits outra vez e redisparar a revogação de acesso.
            if ($atual->status === OrderStatus::Estornado) {
                return $atual;
            }

            // A matriz responde por `PagamentoConfirmado → Estornado` e
            // `Concluido → Estornado`. Um pedido `AguardandoPagamento`,
            // `Cancelado` ou `Expirado` nunca teve dinheiro para devolver, e a
            // reversão é recusada aqui em vez de fabricar um estorno de algo
            // que ninguém pagou.
            $this->exigirTransicao($atual, OrderStatus::Estornado);

            $atual->forceFill([
                'status' => OrderStatus::Estornado,
                'payment_status' => $motivo,
                'reversed_at' => now(),
            ])->save();

            // Um a um, como na confirmação: `reverter()` carrega a transição e o
            // evento que os efeitos escutam. Inclui os pendentes — um split que
            // nunca confirmou não pode seguir aparecendo ao lojista como
            // repasse a caminho num pedido estornado.
            $atual->splits()
                ->whereIn('status', [
                    OrderSplitStatus::Confirmado->value,
                    OrderSplitStatus::Pendente->value,
                ])
                ->get()
                ->each
                ->reverter();

            return $atual->refresh();
        });
    }
}
