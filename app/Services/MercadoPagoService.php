<?php

namespace App\Services;

use App\Enums\OrderSplitStatus;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\SiteSetting;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use RuntimeException;

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

        $payload = array_merge($order->payment_payload ?? [], [
            'payment' => $payment,
        ]);

        $changes = [
            'payment_method' => 'mercado_pago',
            'payment_provider' => 'mercado_pago',
            'payment_status' => $status,
            'mercado_pago_payment_id' => $paymentId,
            'payment_payload' => $payload,
        ];

        if ($status === 'approved') {
            $changes['status'] = OrderStatus::PagamentoConfirmado;
            $changes['paid_at'] = $this->paidAt($payment);
        } elseif (in_array($status, ['cancelled', 'refunded', 'charged_back'], true)) {
            $changes['status'] = OrderStatus::Cancelado;
        }

        $order->forceFill($changes)->save();

        if ($status === 'approved') {
            $order->splits()->update([
                'status' => OrderSplitStatus::Confirmado->value,
                'confirmed_at' => now(),
            ]);
        }

        return $order->refresh();
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
