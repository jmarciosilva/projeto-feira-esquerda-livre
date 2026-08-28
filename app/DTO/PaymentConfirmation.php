<?php

namespace App\DTO;

use Illuminate\Support\Carbon;

/**
 * O que um gateway afirma sobre um pagamento, já validado pelo próprio gateway.
 *
 * Existe para que o domínio não precise conhecer Mercado Pago, Asaas ou
 * qualquer outro provedor: quem integra traduz a resposta do gateway para este
 * formato, e a confirmação do pedido acontece sempre do mesmo jeito.
 *
 * Não carrega decisão nenhuma — só o fato bruto. Se o pagamento pode ou não
 * confirmar aquele pedido é questão do domínio, resolvida em
 * `ConfirmOrderPayment`.
 */
final class PaymentConfirmation
{
    /**
     * @param  string  $provider  identificador do gateway ('mercado_pago', ...)
     * @param  string|null  $externalPaymentId  id do pagamento no gateway
     * @param  float|null  $amount  valor efetivamente aprovado, quando informado
     * @param  Carbon|null  $paidAt  momento da aprovação segundo o gateway
     * @param  array<string, mixed>  $payload  resposta crua, guardada para auditoria
     */
    public function __construct(
        public readonly string $provider,
        public readonly ?string $externalPaymentId,
        public readonly ?float $amount,
        public readonly ?Carbon $paidAt,
        public readonly array $payload = [],
    ) {}
}
