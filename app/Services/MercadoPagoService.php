<?php

namespace App\Services;

use App\Actions\Orders\CancelOrder;
use App\Actions\Payments\ConfirmOrderPayment;
use App\DTO\PaymentConfirmation;
use App\Enums\OrderStatus;
use App\Exceptions\TransicaoDePedidoInvalida;
use App\Models\Order;
use App\Models\SiteSetting;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class MercadoPagoService
{
    private const API_BASE_URL = 'https://api.mercadopago.com';

    public function isEnabled(?SiteSetting $settings = null): bool
    {
        $settings ??= SiteSetting::instance();

        return (bool) $settings->mercado_pago_ativo
            && filled($settings->mercado_pago_access_token);
    }

    /**
     * Cria no gateway a intenção de pagamento deste pedido.
     *
     * Só pedido ainda pagável ganha intenção. Sem esta guarda, os caminhos de
     * repetição — `GET /api/v1/pedidos/{ref}/pagar` e `/pedido/{ref}/pagar` —
     * criariam uma preferência para um pedido cancelado ou expirado, e o
     * cliente veria uma tela de pagamento de algo que o domínio já encerrou.
     */
    public function createPreference(Order $order): Order
    {
        $settings = SiteSetting::instance();
        $this->ensureConfigured($settings);

        if (! $order->status->podeIrPara(OrderStatus::PagamentoConfirmado)) {
            throw new TransicaoDePedidoInvalida($order->status, OrderStatus::PagamentoConfirmado);
        }

        $order->loadMissing(['items.product', 'user']);

        $payload = $this->preferencePayload($order);

        try {
            $response = Http::withToken($settings->mercado_pago_access_token)
                ->acceptJson()
                ->asJson()
                ->withHeaders([
                    'X-Idempotency-Key' => 'fel-preference-'.$order->reference,
                ])
                ->post(self::API_BASE_URL.'/checkout/preferences', $payload)
                ->throw();
        } catch (RequestException $exception) {
            throw new RuntimeException('Não foi possível iniciar o pagamento pelo Mercado Pago.', previous: $exception);
        }

        $data = $response->json();

        // A guarda acima leu o estado **antes** da chamada HTTP, e entre uma
        // coisa e outra cabe o varredor: um pedido cuja janela de checkout
        // vencia durante a viagem pela rede pode ter virado `Expirado`, com o
        // estoque ja devolvido. Gravar a preferencia por cima disso deixaria um
        // pedido encerrado exibindo tela de pagamento.
        //
        // A verificacao e refeita sob lock, mas **so na escrita**: a transacao
        // abre depois que a rede terminou, e dura o tempo de um UPDATE. Manter
        // o lock durante o I/O externo prolongaria a contencao pelo tempo da
        // internet, que e justamente o que a FIN-SEC-01E existe para evitar.
        return DB::transaction(function () use ($order, $data) {
            $atual = Order::query()->whereKey($order->getKey())->lockForUpdate()->firstOrFail();

            if (! $atual->status->podeIrPara(OrderStatus::PagamentoConfirmado)) {
                throw new TransicaoDePedidoInvalida($atual->status, OrderStatus::PagamentoConfirmado);
            }

            $atual->forceFill([
                'payment_method' => 'mercado_pago',
                'payment_provider' => 'mercado_pago',
                'payment_status' => 'pending',
                'mercado_pago_preference_id' => $data['id'] ?? null,
                'mercado_pago_init_point' => $data['init_point'] ?? null,
                'mercado_pago_sandbox_init_point' => $data['sandbox_init_point'] ?? null,
                'payment_payload' => array_merge($atual->payment_payload ?? [], [
                    'preference' => $data,
                ]),
            ])->save();

            return $atual->refresh();
        });
    }

    public function checkoutUrl(Order $order): string
    {
        $settings = SiteSetting::instance();

        $url = $settings->mercado_pago_sandbox
            ? ($order->mercado_pago_sandbox_init_point ?: $order->mercado_pago_init_point)
            : $order->mercado_pago_init_point;

        if (! filled($url)) {
            throw new RuntimeException('O link de pagamento do Mercado Pago ainda não foi gerado.');
        }

        return $url;
    }

    /**
     * Cria e processa um pagamento direto via Checkout API (Payment Brick embutido),
     * sem redirecionar o cliente para fora do site.
     *
     * @param  array<string, mixed>  $formData  Payload gerado pelo Payment Brick (onSubmit).
     * @return array<string, mixed>
     */
    public function createPayment(Order $order, array $formData): array
    {
        $settings = SiteSetting::instance();
        $this->ensureConfigured($settings);

        // O valor cobrado nunca vem do navegador — sempre recalculado a partir do pedido.
        $payload = array_merge($formData, [
            'transaction_amount' => round((float) $order->total_amount, 2),
            'external_reference' => $order->reference,
            'description' => "Pedido #{$order->reference} - Feira Esquerda Livre",
            'notification_url' => route('mercado-pago.webhook'),
        ]);

        try {
            $response = Http::withToken($settings->mercado_pago_access_token)
                ->acceptJson()
                ->asJson()
                ->withHeaders([
                    'X-Idempotency-Key' => 'fel-payment-'.$order->reference.'-'.substr(sha1(json_encode($formData)), 0, 16),
                ])
                ->post(self::API_BASE_URL.'/v1/payments', $payload)
                ->throw();
        } catch (RequestException $exception) {
            throw new RuntimeException($this->paymentErrorMessage($exception), previous: $exception);
        }

        $payment = $response->json();

        $this->applyPayment($payment);

        return $payment;
    }

    /**
     * @return array<string, mixed>
     */
    public function getPayment(string $paymentId): array
    {
        $settings = SiteSetting::instance();
        $this->ensureConfigured($settings);

        try {
            return Http::withToken($settings->mercado_pago_access_token)
                ->acceptJson()
                ->get(self::API_BASE_URL.'/v1/payments/'.$paymentId)
                ->throw()
                ->json();
        } catch (RequestException $exception) {
            throw new RuntimeException('Não foi possível consultar o pagamento no Mercado Pago.', previous: $exception);
        }
    }

    private const EVENTO_APROVACAO = 'aprovacao';

    private const EVENTO_REVERSAO = 'reversao';

    private const EVENTO_CANCELAMENTO = 'cancelamento';

    private const EVENTO_DESCONHECIDO = 'desconhecido';

    /**
     * Traduz a resposta do Mercado Pago para o domínio.
     *
     * Este serviço integra o gateway: sabe ler o payload, normalizar status e
     * guardar a resposta crua para auditoria. Ele **não** decide que um pedido
     * passou a estar pago — essa transição pertence a `ConfirmOrderPayment`,
     * que é a mesma para qualquer gateway e roda atômica e uma única vez.
     *
     * ## Roteamento por natureza, e não por identidade (FIN-SEC-01F-A)
     *
     * Até aqui a única pergunta feita era "este id é diferente do que pagou o
     * pedido?", e qualquer resposta afirmativa desviava a notificação para o
     * rastro de auditoria. A pergunta protegia o caso certo — um segundo
     * `approved` não pode sobrescrever o pagamento que quitou o pedido — mas
     * confundia **identidade do recurso** com **natureza do evento**: uma
     * reversão legítima do pagamento vigente caía no mesmo desvio e sumia.
     *
     * Agora a natureza decide o fluxo, e a identidade decide se o evento
     * pertence a este pedido. São perguntas diferentes, e as duas são feitas.
     *
     * @param  array<string, mixed>  $payment
     */
    public function applyPayment(array $payment): ?Order
    {
        $reference = $payment['external_reference'] ?? null;

        if (! $reference) {
            return null;
        }

        $order = Order::where('reference', $reference)->first();

        if (! $order) {
            return null;
        }

        $status = (string) ($payment['status'] ?? 'unknown');
        $paymentId = isset($payment['id']) ? (string) $payment['id'] : null;

        return match ($this->naturezaDoEvento($status, $order)) {
            self::EVENTO_APROVACAO => $this->rotearAprovacao($order, $payment, $paymentId, $status),
            // O recurso buscado em `getPayment()` **é** o pagamento; por isso,
            // e só por isso, o próprio id serve de correlação quando o payload
            // não traz `payment_id`.
            self::EVENTO_REVERSAO => $this->rotearReversao(
                $order,
                $payment,
                isset($payment['payment_id']) ? (string) $payment['payment_id'] : $paymentId,
                $status,
            ),
            self::EVENTO_CANCELAMENTO => $this->rotearCancelamento($order, $payment, $paymentId, $status),
            default => $this->apenasRegistrar($order, $payment, $paymentId, $status),
        };
    }

    /**
     * Aplica um recurso de **refund**, que é distinto de um pagamento.
     *
     * `GET /v1/payments/{payment_id}/refunds/{refund_id}` devolve um objeto
     * cujo `id` identifica o estorno e cujo `payment_id` identifica o pagamento
     * revertido. Confundir os dois seria assumir `refund.id === payment.id`,
     * que é falso — e correlacionar por um id errado é pior do que não
     * correlacionar. Sem `payment_id`, **fail-closed**: nada acontece.
     *
     * @param  array<string, mixed>  $refund
     */
    public function applyRefund(array $refund): ?Order
    {
        $pagamentoRevertido = isset($refund['payment_id']) ? (string) $refund['payment_id'] : null;

        if ($pagamentoRevertido === null) {
            return null;
        }

        $order = Order::where('mercado_pago_payment_id', $pagamentoRevertido)->first();

        if (! $order) {
            return null;
        }

        return $this->rotearReversao($order, $refund, $pagamentoRevertido, 'refunded');
    }

    /**
     * Aplica uma notificação de chargeback.
     *
     * O chargeback é recurso próprio, com tópico próprio, e sua notificação
     * **não carrega `external_reference`**. Quem liga o evento ao pedido é
     * `data.payment_id` — e é por ele, nunca por `data.id`, que o pedido é
     * localizado: `data.id` identifica o chargeback, não o pagamento.
     *
     * @param  array<string, mixed>  $notificacao  campos mínimos já extraídos
     */
    public function applyChargeback(array $notificacao): ?Order
    {
        $chargebackId = isset($notificacao['id']) ? (string) $notificacao['id'] : null;
        $paymentId = isset($notificacao['payment_id']) ? (string) $notificacao['payment_id'] : null;

        if ($paymentId === null) {
            // Sem o vínculo com o pagamento não há o que correlacionar, e
            // adivinhar seria pior do que não agir.
            return null;
        }

        $order = Order::where('mercado_pago_payment_id', $paymentId)->first();

        if (! $order) {
            return null;
        }

        return $this->rotearReversao(
            $order,
            ['chargeback_id' => $chargebackId, 'payment_id' => $paymentId],
            $paymentId,
            'charged_back',
        );
    }

    /**
     * Reversão e cancelamento chegam sob o mesmo `cancelled` do gateway; o que
     * os separa é o pedido já ter sido pago ou não. Cancelar o que nunca foi
     * pago encerra uma intenção de compra; "cancelar" o que já foi pago é
     * reversão financeira, e não pode seguir pelo mesmo caminho.
     */
    private function naturezaDoEvento(string $status, Order $order): string
    {
        $jaPago = $order->status === OrderStatus::PagamentoConfirmado
            || $order->paid_at !== null;

        return match (true) {
            $status === 'approved' => self::EVENTO_APROVACAO,
            in_array($status, ['refunded', 'charged_back'], true) => self::EVENTO_REVERSAO,
            $status === 'cancelled' && $jaPago => self::EVENTO_REVERSAO,
            $status === 'cancelled' => self::EVENTO_CANCELAMENTO,
            default => self::EVENTO_DESCONHECIDO,
        };
    }

    /**
     * Aprovação. Aqui mora, intacta, a proteção da FIN-SEC-01D.
     *
     * @param  array<string, mixed>  $payment
     */
    private function rotearAprovacao(Order $order, array $payment, ?string $paymentId, string $status): Order
    {
        if ($this->ehOutroPagamento($order, $paymentId)) {
            // Segundo `approved` para um pedido já quitado por outro pagamento:
            // o payload vira rastro de auditoria e nada mais. Sobrescrever o id
            // faria o pedido apontar para o pagamento errado, e reconfirmar
            // consumiria estoque e confirmaria splits de novo.
            return $this->guardarRastro($order, $payment, 'payment_ignorado_'.$paymentId);
        }

        $this->gravarPagamentoVigente($order, $payment, $paymentId, $status);

        return $this->confirmar($order, $payment, $paymentId);
    }

    /**
     * Reversão financeira — refund ou chargeback.
     *
     * A autoridade não é `external_reference`, que identifica o **pedido**: um
     * pedido pode ter mais de uma tentativa de pagamento, e revertê-lo por
     * causa de um pagamento que não foi o que o quitou destruiria uma venda
     * válida. A autoridade é o pagamento revertido bater com o vigente.
     *
     * **Fail-closed** quer dizer: nenhuma mutação comercial ou financeira
     * operacional. Nem status, nem split, nem estoque, nem matrícula, nem
     * `paid_at`, `payment_status` ou autoridade de pagamento. O que acontece é
     * uma escrita de auditoria em `payment_payload`, sob chave própria — o
     * evento fica registrado justamente para poder ser investigado.
     *
     * Nesta subfase a reversão correlacionada é registrada e **não** transiciona
     * o pedido: `OrderStatus::Estornado`, `OrderSplitStatus::Revertido` e a
     * revogação da matrícula pertencem à 01F-D.
     *
     * Quem chama já resolveu a correlação — e cada chamador sabe de que tipo é
     * o recurso que tem em mãos. Resolver aqui dentro obrigaria este método a
     * adivinhar, e adivinhar produziria justamente o `refund.id === payment.id`
     * que não se pode assumir.
     *
     * @param  array<string, mixed>  $payment
     * @param  string|null  $pagamentoRevertido  id do pagamento revertido, já correlacionado
     */
    private function rotearReversao(Order $order, array $payment, ?string $pagamentoRevertido, string $status): Order
    {
        $vigente = $order->mercado_pago_payment_id;

        if ($vigente === null || $pagamentoRevertido === null || $vigente !== $pagamentoRevertido) {
            return $this->guardarRastro(
                $order,
                $payment,
                'reversao_nao_relacionada_'.($pagamentoRevertido ?? 'sem_id'),
            );
        }

        return $this->guardarRastro($order, $payment, 'reversao_'.$status.'_'.$pagamentoRevertido);
    }

    /**
     * Cancelamento antes do pagamento: encerra a intenção de compra.
     *
     * O gateway informa o fato; quem transiciona é o domínio. Até a 01F-B este
     * método escrevia `Cancelado` direto e deixava a reserva de estoque presa
     * para sempre — o vazamento V-1. `CancelOrder` devolve as unidades na mesma
     * transação em que encerra o pedido.
     *
     * Se o pedido já não puder ser cancelado — expirado, concluído —, a recusa
     * é registrada em vez de virar erro: o gateway não tem culpa de chegar
     * atrasado, e o estado do domínio prevalece.
     *
     * @param  array<string, mixed>  $payment
     */
    private function rotearCancelamento(Order $order, array $payment, ?string $paymentId, string $status): Order
    {
        $this->gravarPagamentoVigente($order, $payment, $paymentId, $status);

        try {
            return app(CancelOrder::class)($order->refresh());
        } catch (TransicaoDePedidoInvalida $recusada) {
            report($recusada);

            return $this->guardarRastro($order, $payment, 'cancelamento_recusado_'.($paymentId ?? 'sem_id'));
        }
    }

    /**
     * Status que o domínio não roteia — `pending`, `in_process` e afins.
     *
     * @param  array<string, mixed>  $payment
     */
    private function apenasRegistrar(Order $order, array $payment, ?string $paymentId, string $status): Order
    {
        if ($this->ehOutroPagamento($order, $paymentId)) {
            return $this->guardarRastro($order, $payment, 'payment_ignorado_'.$paymentId);
        }

        $this->gravarPagamentoVigente($order, $payment, $paymentId, $status);

        return $order->refresh();
    }

    private function ehOutroPagamento(Order $order, ?string $paymentId): bool
    {
        return $order->status === OrderStatus::PagamentoConfirmado
            && $order->mercado_pago_payment_id !== null
            && $paymentId !== null
            && $order->mercado_pago_payment_id !== $paymentId;
    }

    /**
     * Guarda o payload sob uma chave própria, sem tocar em nada do domínio.
     *
     * @param  array<string, mixed>  $payload
     */
    private function guardarRastro(Order $order, array $payload, string $chave): Order
    {
        $order->forceFill([
            'payment_payload' => array_merge($order->payment_payload ?? [], [$chave => $payload]),
        ])->save();

        return $order->refresh();
    }

    /**
     * @param  array<string, mixed>  $payment
     */
    private function gravarPagamentoVigente(Order $order, array $payment, ?string $paymentId, string $status): void
    {
        $campos = [
            'payment_payload' => array_merge($order->payment_payload ?? [], ['payment' => $payment]),
            'payment_method' => 'mercado_pago',
            'payment_provider' => 'mercado_pago',
            'payment_status' => $status,
            'mercado_pago_payment_id' => $paymentId,
        ];

        $prazo = $this->prazoDePagamento($payment);

        // Só grava quando o gateway realmente informou um instante válido.
        // Ausência não apaga o prazo que já estava lá — uma notificação
        // posterior sem o campo não pode tornar imortal um Pix que vencia.
        if ($prazo !== null) {
            $campos['payment_expires_at'] = $prazo;
        }

        $order->forceFill($campos)->save();
    }

    /**
     * Traduz `date_of_expiration` do gateway para o instante do domínio.
     *
     * A autoridade operacional da expiração passa a ser
     * `orders.payment_expires_at`; `payment_payload` continua existindo como
     * evidência, mas nenhuma decisão de domínio o consulta.
     *
     * Três casos, deliberadamente distintos:
     *
     * - **valor válido** — normaliza e persiste. O instante é inequívoco porque
     *   o offset é exigido; o cast `datetime` guarda em UTC e devolve no fuso
     *   da aplicação.
     * - **campo ausente ou nulo** — `null`, e nada é gravado. Nem todo meio de
     *   pagamento tem prazo, e a ausência é informação legítima.
     * - **qualquer outra coisa** — `null`, com registro operacional.
     *
     * ## Por que o formato é exigido, e não apenas tentado
     *
     * `Carbon::parse()` **é** permissivo: aceita `"tomorrow"`, `"+1 day"` e
     * outras expressões humanas, devolvendo um instante plausível sem reclamar.
     * Isso é exatamente o que não se pode ter aqui — um campo corrompido, ou um
     * dia trocado por outro serviço, viraria um prazo real e expiraria um
     * pedido legítimo.
     *
     * Por isso o contrato é declarado: ISO-8601 com **offset explícito**, que é
     * o que o Mercado Pago documenta (`2026-08-29T18:30:00.000-03:00`). Aceito
     * as duas formas legítimas de escrever o offset — `-03:00` e `Z` —, e
     * também a fração de segundo opcional, porque essas variações são todas
     * ISO-8601 válidas e um gateway pode alternar entre elas. O que não passa é
     * texto livre.
     *
     * @param  array<string, mixed>  $payment
     */
    private function prazoDePagamento(array $payment): ?Carbon
    {
        $bruto = $payment['date_of_expiration'] ?? null;

        if (! is_string($bruto) || trim($bruto) === '') {
            return null;
        }

        $bruto = trim($bruto);

        // AAAA-MM-DDTHH:MM:SS[.frações](Z|±HH:MM|±HHMM)
        $iso8601ComOffset = '/^\\d{4}-\\d{2}-\\d{2}[T ]\\d{2}:\\d{2}:\\d{2}(\\.\\d{1,6})?(Z|[+-]\\d{2}:?\\d{2})$/';

        if (preg_match($iso8601ComOffset, $bruto) !== 1) {
            $this->registrarPrazoRecusado($payment, 'fora do contrato ISO-8601 com offset');

            return null;
        }

        try {
            // Convertido para o fuso da aplicacao antes de persistir. O cast
            // `datetime` grava a hora de parede que o objeto carrega e a rele
            // assumindo `app.timezone`: um prazo em `Z` seria gravado em UTC e
            // relido como horario local, deslocando o vencimento em horas.
            // Converter aqui faz `Z` e `-03:00` chegarem ao mesmo instante.
            return Carbon::parse($bruto)->setTimezone(config('app.timezone'));
        } catch (Throwable $erroDeFormato) {
            $this->registrarPrazoRecusado($payment, $erroDeFormato->getMessage());

            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $payment
     */
    private function registrarPrazoRecusado(array $payment, string $motivo): void
    {
        // Metadado apenas: o payload cru pode conter dado do pagador.
        Log::warning('mercado_pago.expiracao.invalida', [
            'payment_id' => isset($payment['id']) ? (string) $payment['id'] : null,
            'motivo' => $motivo,
        ]);
    }

    /**
     * @param  array<string, mixed>  $payment
     */
    private function confirmar(Order $order, array $payment, ?string $paymentId): Order
    {
        $confirmacao = new PaymentConfirmation(
            provider: 'mercado_pago',
            externalPaymentId: $paymentId,
            amount: isset($payment['transaction_amount']) && is_numeric($payment['transaction_amount'])
                ? round((float) $payment['transaction_amount'], 2)
                : null,
            paidAt: $this->paidAt($payment),
            payload: $payment,
        );

        try {
            return app(ConfirmOrderPayment::class)($order, $confirmacao);
        } catch (Throwable $exception) {
            // Pagamento aprovado no gateway que o domínio recusa — valor
            // insuficiente, por exemplo. O pedido fica como está, com o rastro
            // do gateway já gravado acima, e o erro sobe para o log em vez de
            // virar uma confirmação silenciosa.
            report($exception);

            return $order->refresh();
        }
    }

    public function syncPayment(string $paymentId): ?Order
    {
        return $this->applyPayment($this->getPayment($paymentId));
    }

    private function ensureConfigured(SiteSetting $settings): void
    {
        if (! $this->isEnabled($settings)) {
            throw new RuntimeException('Mercado Pago não está ativo ou está sem Access Token.');
        }
    }

    private function paymentErrorMessage(RequestException $exception): string
    {
        $response = $exception->response;
        $message = $response?->json('message') ?: $response?->json('cause.0.description');

        return is_string($message) && $message !== ''
            ? "Mercado Pago: {$message}"
            : 'Não foi possível processar o pagamento pelo Mercado Pago agora.';
    }

    /**
     * @return array<string, mixed>
     */
    private function preferencePayload(Order $order): array
    {
        $items = $order->items->map(fn ($item) => [
            'id' => (string) $item->product_id,
            'title' => $item->product_name,
            'description' => $item->product?->description ?: $item->product_name,
            'quantity' => (int) $item->quantity,
            'currency_id' => 'BRL',
            'unit_price' => round((float) $item->unit_price, 2),
        ])->values()->all();

        if ((float) $order->shipping_total > 0) {
            $items[] = [
                'id' => 'shipping',
                'title' => 'Frete',
                'description' => $order->shipping_note ?: 'Frete selecionado no checkout',
                'quantity' => 1,
                'currency_id' => 'BRL',
                'unit_price' => round((float) $order->shipping_total, 2),
            ];
        }

        $payload = [
            'items' => $items,
            'external_reference' => $order->reference,
            'notification_url' => route('mercado-pago.webhook'),
            'back_urls' => [
                'success' => route('mercado-pago.return', ['reference' => $order->reference, 'resultado' => 'sucesso']),
                'failure' => route('mercado-pago.return', ['reference' => $order->reference, 'resultado' => 'falha']),
                'pending' => route('mercado-pago.return', ['reference' => $order->reference, 'resultado' => 'pendente']),
            ],
            'statement_descriptor' => 'FEIRA ESQ LIVRE',
            'metadata' => [
                'order_id' => $order->id,
                'order_reference' => $order->reference,
            ],
        ];

        if ($this->canUseAutoReturn()) {
            $payload['auto_return'] = 'approved';
        }

        if ($order->customer_email) {
            $payload['payer'] = [
                'name' => $order->customer_name,
                'email' => $order->customer_email,
            ];
        }

        return $payload;
    }

    private function canUseAutoReturn(): bool
    {
        $host = parse_url((string) config('app.url'), PHP_URL_HOST);

        if (! $host) {
            return false;
        }

        return ! in_array($host, ['localhost', '127.0.0.1', '::1'], true)
            && ! str_ends_with($host, '.test')
            && ! str_ends_with($host, '.local');
    }

    /**
     * @param  array<string, mixed>  $payment
     */
    private function paidAt(array $payment): Carbon
    {
        $date = $payment['date_approved'] ?? $payment['date_last_updated'] ?? null;

        return $date ? Carbon::parse($date) : now();
    }
}
