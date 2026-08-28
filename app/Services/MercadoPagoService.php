<?php

namespace App\Services;

use App\Actions\Payments\ConfirmOrderPayment;
use App\DTO\PaymentConfirmation;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\SiteSetting;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
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

    public function createPreference(Order $order): Order
    {
        $settings = SiteSetting::instance();
        $this->ensureConfigured($settings);

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

        $order->forceFill([
            'payment_method' => 'mercado_pago',
            'payment_provider' => 'mercado_pago',
            'payment_status' => 'pending',
            'mercado_pago_preference_id' => $data['id'] ?? null,
            'mercado_pago_init_point' => $data['init_point'] ?? null,
            'mercado_pago_sandbox_init_point' => $data['sandbox_init_point'] ?? null,
            'payment_payload' => array_merge($order->payment_payload ?? [], [
                'preference' => $data,
            ]),
        ])->save();

        return $order->refresh();
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

    /**
     * Traduz a resposta do Mercado Pago para o domínio.
     *
     * Este serviço integra o gateway: sabe ler o payload, normalizar status e
     * guardar a resposta crua para auditoria. Ele **não** decide que um pedido
     * passou a estar pago — essa transição pertence a `ConfirmOrderPayment`,
     * que é a mesma para qualquer gateway e roda atômica e uma única vez.
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

        // Notificação de um pagamento diferente, para um pedido que já foi
        // quitado por outro: o payload é guardado como rastro de auditoria, mas
        // o id e o status do pagamento que de fato pagou o pedido permanecem.
        // Sobrescrevê-los faria o pedido apontar para o pagamento errado.
        $jaQuitadoPorOutro = $order->status === OrderStatus::PagamentoConfirmado
            && $order->mercado_pago_payment_id !== null
            && $paymentId !== null
            && $order->mercado_pago_payment_id !== $paymentId;

        $metadados = [
            'payment_payload' => array_merge($order->payment_payload ?? [], [
                $jaQuitadoPorOutro ? 'payment_ignorado_'.$paymentId : 'payment' => $payment,
            ]),
        ];

        if (! $jaQuitadoPorOutro) {
            $metadados += [
                'payment_method' => 'mercado_pago',
                'payment_provider' => 'mercado_pago',
                'payment_status' => $status,
                'mercado_pago_payment_id' => $paymentId,
            ];
        }

        $order->forceFill($metadados)->save();

        if ($jaQuitadoPorOutro) {
            return $order->refresh();
        }

        if ($status === 'approved') {
            return $this->confirmar($order, $payment, $paymentId);
        }

        if (in_array($status, ['cancelled', 'refunded', 'charged_back'], true)) {
            $order->forceFill(['status' => OrderStatus::Cancelado])->save();
        }

        return $order->refresh();
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
