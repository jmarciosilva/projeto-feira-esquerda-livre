<?php

namespace Tests\Feature;

use App\Enums\OrderSplitStatus;
use App\Enums\OrderStatus;
use App\Models\Expositor;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderSplit;
use App\Models\Product;
use App\Models\SiteSetting;
use App\Services\MercadoPagoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MercadoPagoPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_checkout_preference_for_order(): void
    {
        Http::fake([
            'api.mercadopago.com/checkout/preferences' => Http::response([
                'id' => 'pref_123',
                'init_point' => 'https://www.mercadopago.com.br/checkout/v1/redirect?pref_id=pref_123',
                'sandbox_init_point' => 'https://sandbox.mercadopago.com.br/checkout/v1/redirect?pref_id=pref_123',
            ], 201),
        ]);

        $order = $this->makeOrder();
        $this->enableMercadoPago();

        $order = app(MercadoPagoService::class)->createPreference($order);

        $this->assertSame('mercado_pago', $order->payment_method);
        $this->assertSame('pending', $order->payment_status);
        $this->assertSame('pref_123', $order->mercado_pago_preference_id);
        $this->assertSame('https://sandbox.mercadopago.com.br/checkout/v1/redirect?pref_id=pref_123', app(MercadoPagoService::class)->checkoutUrl($order));

        Http::assertSent(function ($request) use ($order) {
            $payload = $request->data();

            return $request->hasHeader('Authorization', 'Bearer TEST_TOKEN')
                && $payload['external_reference'] === $order->reference
                && $payload['items'][0]['title'] === 'Bolsa Tecida'
                && ! array_key_exists('collector_id', $payload)
                && ! array_key_exists('application_fee', $payload);
        });
    }

    public function test_webhook_marks_order_as_paid(): void
    {
        $order = $this->makeOrder([
            'payment_method' => 'mercado_pago',
            'payment_provider' => 'mercado_pago',
            'payment_status' => 'pending',
        ]);
        $this->enableMercadoPago();

        Http::fake([
            'api.mercadopago.com/v1/payments/999' => Http::response([
                'id' => 999,
                'status' => 'approved',
                'external_reference' => $order->reference,
                'date_approved' => '2026-06-29T12:00:00.000-03:00',
            ]),
        ]);

        $response = $this->postJson(route('mercado-pago.webhook'), [
            'type' => 'payment',
            'data' => ['id' => '999'],
        ]);

        $response->assertOk();

        $order->refresh();

        $this->assertSame(OrderStatus::PagamentoConfirmado, $order->status);
        $this->assertSame('approved', $order->payment_status);
        $this->assertSame('999', $order->mercado_pago_payment_id);
        $this->assertNotNull($order->paid_at);
        $this->assertSame(OrderSplitStatus::Confirmado, $order->splits()->first()->status);
    }

    public function test_pay_endpoint_charges_real_order_total_ignoring_client_amount(): void
    {
        $order = $this->makeOrder([
            'payment_method' => 'mercado_pago',
            'payment_provider' => 'mercado_pago',
            'payment_status' => 'pending',
        ]);
        $this->enableMercadoPago();

        Http::fake([
            'api.mercadopago.com/v1/payments' => function ($request) {
                return Http::response([
                    'id' => 555,
                    'status' => 'approved',
                    'status_detail' => 'accredited',
                    'external_reference' => $request['external_reference'],
                    'date_approved' => '2026-06-29T12:00:00.000-03:00',
                ]);
            },
        ]);

        $response = $this->postJson(route('mercado-pago.pay.process', $order->reference), [
            'payment_method_id' => 'pix',
            'transaction_amount' => 0.01, // tentativa de manipular o valor — deve ser ignorado
            'payer' => ['email' => 'cliente@example.com'],
        ]);

        $response->assertOk()->assertJsonPath('status', 'approved');

        Http::assertSent(function ($request) use ($order) {
            $payload = $request->data();

            return (float) $payload['transaction_amount'] === 89.90
                && $payload['external_reference'] === $order->reference;
        });

        $order->refresh();
        $this->assertSame(OrderStatus::PagamentoConfirmado, $order->status);
    }

    public function test_pay_endpoint_returns_pix_qr_code_when_pending(): void
    {
        $order = $this->makeOrder([
            'payment_method' => 'mercado_pago',
            'payment_provider' => 'mercado_pago',
            'payment_status' => 'pending',
        ]);
        $this->enableMercadoPago();

        Http::fake([
            'api.mercadopago.com/v1/payments' => function ($request) {
                return Http::response([
                    'id' => 777,
                    'status' => 'pending',
                    'status_detail' => 'pending_waiting_transfer',
                    'external_reference' => $request['external_reference'],
                    'point_of_interaction' => [
                        'transaction_data' => [
                            'qr_code' => '00020126...copia-e-cola',
                            'qr_code_base64' => 'iVBORw0KGgoAAAANSUhEUg==',
                        ],
                    ],
                ]);
            },
        ]);

        $response = $this->postJson(route('mercado-pago.pay.process', $order->reference), [
            'payment_method_id' => 'pix',
            'payer' => ['email' => 'cliente@example.com'],
        ]);

        $response->assertOk()
            ->assertJsonPath('status', 'pending')
            ->assertJsonPath('pix.qr_code_base64', 'iVBORw0KGgoAAAANSUhEUg==');

        $order->refresh();
        $this->assertSame('pending', $order->payment_status);
    }

    public function test_pedido_show_renders_embedded_payment_brick_when_pending(): void
    {
        $order = $this->makeOrder([
            'payment_method' => 'mercado_pago',
            'payment_provider' => 'mercado_pago',
            'payment_status' => 'pending',
        ]);
        $this->enableMercadoPago();
        SiteSetting::instance()->update(['mercado_pago_public_key' => 'TEST-public-key']);

        $response = $this->get(route('pedido.show', $order->reference));

        $response->assertOk()
            ->assertSee('paymentBrick_container', false)
            ->assertSee('sdk.mercadopago.com/js/v2', false)
            ->assertSee('TEST-public-key', false)
            ->assertDontSee('Pagar agora com Mercado Pago');
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function makeOrder(array $attributes = []): Order
    {
        $expositor = Expositor::create([
            'name' => 'Atelie das Maos',
            'slug' => 'atelie-das-maos',
        ]);

        $product = Product::create([
            'expositor_id' => $expositor->id,
            'name' => 'Bolsa Tecida',
            'slug' => 'bolsa-tecida',
            'description' => 'Bolsa artesanal',
            'price' => 89.90,
            'is_active' => true,
        ]);

        $order = Order::create(array_merge([
            'customer_name' => 'Cliente Teste',
            'customer_whatsapp' => '(11) 99999-9999',
            'customer_email' => 'cliente@example.com',
            'delivery_type' => 'retirada',
            'items_total' => 89.90,
            'shipping_total' => 0,
            'shipping_note' => 'Retirada combinada com a loja.',
            'total_amount' => 89.90,
            'status' => OrderStatus::AguardandoPagamento,
        ], $attributes));

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'expositor_id' => $expositor->id,
            'product_name' => $product->name,
            'unit_price' => 89.90,
            'quantity' => 1,
            'total_price' => 89.90,
        ]);

        OrderSplit::create([
            'order_id' => $order->id,
            'expositor_id' => $expositor->id,
            'gross_amount' => 89.90,
            'commission_percent' => 10,
            'commission_amount' => 8.99,
            'net_amount' => 80.91,
            'status' => OrderSplitStatus::Pendente,
        ]);

        return $order->refresh();
    }

    private function enableMercadoPago(): void
    {
        SiteSetting::instance()->update([
            'mercado_pago_ativo' => true,
            'mercado_pago_access_token' => 'TEST_TOKEN',
            'mercado_pago_sandbox' => true,
        ]);
    }
}
