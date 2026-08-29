<?php

namespace App\Enums;

/**
 * A natureza de um desencontro entre o que o gateway afirma e o que o domínio
 * aceita.
 *
 * Um conflito financeiro nasce sempre da mesma situação: **o dinheiro se moveu
 * e o pedido não pôde acompanhar**. Até a FIN-SEC-01F-D isso não deixava
 * registro nenhum — a confirmação falhava, a transação era desfeita, e o
 * rollback levava junto a única evidência de que algo havia chegado.
 *
 * Cada caso responde uma pergunta diferente para quem reconcilia depois:
 *
 * - **InsufficientStock** — o gateway aprovou e não havia unidades para
 *   atender. Dinheiro capturado, pedido inatendível.
 * - **PaymentAfterTerminal** — aprovação chegou sobre pedido `Cancelado` ou
 *   `Estornado`. Alguém encerrou a compra antes.
 * - **PaymentAfterExpiration** — aprovação chegou sobre pedido `Expirado`.
 *   Distinto do anterior de propósito: aqui o estoque **já voltou à
 *   prateleira** e pode ter sido vendido a outra pessoa, então o caminho de
 *   reconciliação não é o mesmo.
 * - **UnmatchedReversal** — reversão que o domínio não pôde aplicar. Duas
 *   origens, distinguidas em `context.motivo`: ela fala de um pagamento que não
 *   é o vigente do pedido (`sem_correlacao`) — aplicá-la destruiria uma venda
 *   válida —, ou fala do pagamento certo sobre um pedido que nunca foi pago ou
 *   já encerrou por outro caminho (`transicao_recusada`).
 * - **AmountMismatch** — o valor aprovado não é o valor do pedido.
 * - **PartialRefundUnsupported** — devolução parcial, que o domínio ainda não
 *   sabe representar sem mentir. Ver `ChargebackUnverified` para o outro caso
 *   em que registrar é preferível a transicionar.
 * - **ChargebackUnverified** — chegou notificação de chargeback, e o domínio
 *   não tem evidência do desfecho dele. Um chargeback aberto não é dinheiro
 *   perdido; pode ser disputado e revertido.
 * - **UnexpectedCancellationAfterPayment** — o gateway diz que o pagamento foi
 *   cancelado, e o domínio já tem evidência histórica de que ele foi
 *   confirmado. `cancelled` não é `refunded`: não traz valor devolvido nem
 *   recurso de estorno, e portanto não prova reversão financeira nenhuma.
 */
enum PaymentConflictType: string
{
    case InsufficientStock = 'insufficient_stock';
    case PaymentAfterTerminal = 'payment_after_terminal';
    case PaymentAfterExpiration = 'payment_after_expiration';
    case UnmatchedReversal = 'unmatched_reversal';
    case AmountMismatch = 'amount_mismatch';
    case PartialRefundUnsupported = 'partial_refund_unsupported';
    case ChargebackUnverified = 'chargeback_unverified';
    case UnexpectedCancellationAfterPayment = 'unexpected_cancellation_after_payment';

    public function label(): string
    {
        return match ($this) {
            self::InsufficientStock => 'Pagamento sem estoque para atender',
            self::PaymentAfterTerminal => 'Pagamento após encerramento do pedido',
            self::PaymentAfterExpiration => 'Pagamento após expiração do pedido',
            self::UnmatchedReversal => 'Reversão que o pedido não pôde absorver',
            self::AmountMismatch => 'Valor aprovado diferente do valor do pedido',
            self::PartialRefundUnsupported => 'Devolução parcial ainda não representável',
            self::ChargebackUnverified => 'Chargeback sem desfecho conhecido',
            self::UnexpectedCancellationAfterPayment => 'Cancelamento sobre pagamento já confirmado',
        };
    }
}
