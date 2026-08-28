<?php

namespace App\Actions\Payments;

use App\DTO\PaymentConfirmation;
use App\Enums\OrderSplitStatus;
use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * A transição de domínio "este pedido foi pago".
 *
 * Antes, três caminhos diferentes — o Payment Brick, o retorno do gateway e o
 * webhook — marcavam o pedido como pago cada um por conta própria, com duas
 * escritas soltas: `$order->save()` e um `update()` em massa nos splits. Duas
 * consequências, ambas reproduzidas em teste:
 *
 * 1. o update em massa não instancia models e **não dispara**
 *    `OrderSplitConfirmed`, então quem comprava um curso digital pagando por
 *    Pix ou cartão não era matriculado até o lojista clicar em "confirmar";
 * 2. sem transação, uma falha entre as duas escritas deixava pedido pago com
 *    split pendente.
 *
 * Aqui isso vira uma coisa só: ou o pedido passa a pago **com** todos os splits
 * confirmados e os efeitos disparados, ou nada acontece.
 *
 * ## Gateway nenhum decide isto
 *
 * A integração apenas afirma "este pagamento foi aprovado", traduzido em
 * `PaymentConfirmation`. Quem decide se a afirmação confirma o pedido é esta
 * ação — e é por isso que ela não menciona Mercado Pago em lugar nenhum.
 */
final class ConfirmOrderPayment
{
    /**
     * @return Order o pedido no estado final — pago agora, ou já pago antes
     *
     * @throws RuntimeException quando o pedido está cancelado, o pagamento vem
     *                          sem valor confiável, ou o valor aprovado não
     *                          corresponde ao total do pedido
     */
    public function __invoke(Order $order, PaymentConfirmation $pagamento): Order
    {
        return DB::transaction(function () use ($order, $pagamento) {
            // Trava a linha antes de ler o estado: dois webhooks simultâneos,
            // ou um webhook competindo com o retorno do checkout, chegam aqui
            // ao mesmo tempo. O segundo espera, relê e encontra o pedido já
            // pago — em vez de repetir a transição.
            $atual = Order::query()
                ->whereKey($order->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            // Ja pago: a confirmacao repetida e a mesma transicao chegando de
            // novo, e devolver o estado atual e a resposta idempotente.
            if (in_array($atual->status, [OrderStatus::PagamentoConfirmado, OrderStatus::Concluido], true)) {
                return $atual;
            }

            $this->recusarPedidoTerminal($atual);
            $this->recusarValorDivergente($atual, $pagamento);

            $atual->forceFill([
                'payment_provider' => $pagamento->provider,
                'payment_status' => 'approved',
                'status' => OrderStatus::PagamentoConfirmado,
                // Momento da **primeira** confirmação legítima. Reconfirmações
                // posteriores param no early return acima e não chegam aqui.
                'paid_at' => $pagamento->paidAt ?? now(),
            ])->save();

            // Um a um, e só os pendentes: `confirmar()` carrega a transição de
            // estado e o evento que os efeitos de negócio escutam. O update em
            // massa que existia aqui era mais rápido e não avisava ninguém.
            $atual->splits()
                ->where('status', OrderSplitStatus::Pendente->value)
                ->get()
                ->each
                ->confirmar();

            return $atual;
        });
    }

    /**
     * Um pedido cancelado não volta a viver porque um aprovado chegou atrasado.
     *
     * Cancelamento é estado terminal: reabri-lo aqui, em silêncio, produziria um
     * pedido pago que ninguém espera atender. O que fazer com um pagamento que
     * chega depois do cancelamento — estornar, reabrir mediante decisão humana —
     * é assunto do ciclo de cancelamento, na FIN-SEC-01F.
     */
    private function recusarPedidoTerminal(Order $order): void
    {
        if ($order->status === OrderStatus::Cancelado) {
            throw new RuntimeException(sprintf(
                'O pedido %s está cancelado e não pode ser confirmado por um pagamento posterior.',
                $order->reference,
            ));
        }
    }

    /**
     * Aprovado no gateway não é o mesmo que corresponder ao pedido.
     *
     * A comparação é por **igualdade**: pagar R$ 1 num pedido de R$ 500 é tão
     * inconsistente quanto pagar R$ 999. Nos dois casos o que o gateway aprovou
     * não é o que este pedido cobra, e confirmar seria registrar um fato
     * financeiro que não aconteceu.
     *
     * ## Sem valor não há confirmação
     *
     * Um `approved` que chega sem valor legível — campo ausente, nulo, texto,
     * negativo — não confirma nada. Antes esse caso passava direto pela
     * validação, apostando que o gateway sempre informa o valor; a aposta não
     * pertence a uma regra de domínio financeiro, que precisa ser fail closed.
     */
    private function recusarValorDivergente(Order $order, PaymentConfirmation $pagamento): void
    {
        if ($pagamento->amount === null || $pagamento->amount < 0) {
            throw new RuntimeException(sprintf(
                'Pagamento aprovado para o pedido %s sem valor confiável — confirmação recusada.',
                $order->reference,
            ));
        }

        $pago = $this->emCentavos($pagamento->amount);
        $esperado = $this->emCentavos((float) $order->total_amount);

        if ($pago !== $esperado) {
            throw new RuntimeException(sprintf(
                'Pagamento de %d centavos não corresponde ao pedido %s, de %d centavos.',
                $pago,
                $order->reference,
                $esperado,
            ));
        }
    }

    /**
     * Converte reais em centavos inteiros, para comparar dinheiro sem ponto
     * flutuante.
     *
     * `abs($a - $b) > 0.01` dependia de como o IEEE-754 representa cada lado:
     * `499.99 * 100` vale 49998.999999999993 em binário, e um `(int)` direto
     * truncaria para 49998. O `round()` antes do corte resolve isso, e a
     * comparação passa a ser entre dois inteiros.
     *
     * ## Política de arredondamento
     *
     * O real não representa frações de centavo, então o valor é arredondado
     * para o centavo mais próximo antes da comparação: R$ 500,001 e R$ 500,00
     * são o mesmo dinheiro e confirmam um pedido de R$ 500,00; R$ 500,006 já
     * arredonda para R$ 500,01 e é recusado, como qualquer outro centavo de
     * diferença.
     */
    private function emCentavos(float $valor): int
    {
        return (int) round($valor * 100);
    }
}
