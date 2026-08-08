<?php

namespace Tests\Feature\CustomerIntelligence;

use App\Enums\ShippingStatus;
use App\Livewire\Checkout;
use App\Livewire\Lojista\Pedidos\PedidoIndex;
use App\Models\CartItem;
use App\Models\Expositor;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderShipping;
use App\Models\OrderSplit;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use JmfSystem\CustomerIntelligence\Jobs\SendPayloadJob;
use Livewire\Livewire;
use Tests\TestCase;

class EventTrackingTest extends TestCase
{
    use RefreshDatabase;

    private function makeExpositor(): Expositor
    {
        static $counter = 0;
        $counter++;

        $expositor = Expositor::create(['name' => "Loja CI {$counter}", 'slug' => "loja-ci-{$counter}"]);
        $expositor->update(['user_id' => User::factory()->create(['role' => \App\Enums\UserRole::Lojista])->id]);

        return $expositor;
    }

    private function makeProduct(Expositor $expositor): Product
    {
        return Product::create([
            'expositor_id' => $expositor->id,
            'item_type' => 'produto',
            'name' => 'Bolsa Artesanal',
            'slug' => 'bolsa-artesanal-'.uniqid(),
            'price' => 89.90,
            'is_active' => true,
        ]);
    }

    private function assertEventTracked(string $eventName, ?callable $propertiesCallback = null): void
    {
        Bus::assertDispatched(SendPayloadJob::class, function (SendPayloadJob $job) use ($eventName, $propertiesCallback) {
            if ($job->endpoint !== 'events' || $job->payload['event_name'] !== $eventName) {
                return false;
            }

            if ($propertiesCallback === null) {
                return true;
            }

            return $propertiesCallback($job->payload['properties'] ?? []);
        });
    }

    // ─── produto.visualizado ──────────────────────────────────────────────

    public function test_viewing_product_page_tracks_produto_visualizado(): void
    {
        Bus::fake();

        $expositor = $this->makeExpositor();
        $product = $this->makeProduct($expositor);

        $this->get(route('loja.produto', [$expositor->slug, $product->slug]))->assertOk();

        $this->assertEventTracked('produto.visualizado', fn (array $props) => $props['produto_id'] === $product->id
            && $props['expositor_id'] === $expositor->id);
    }

    // ─── carrinho ─────────────────────────────────────────────────────────

    public function test_adding_to_cart_tracks_produto_adicionado_carrinho(): void
    {
        Bus::fake();

        $expositor = $this->makeExpositor();
        $product = $this->makeProduct($expositor);

        app(CartService::class)->add($product, 2);

        $this->assertEventTracked('produto.adicionado_carrinho', fn (array $props) => $props['produto_id'] === $product->id
            && $props['quantidade'] === 2);
    }

    public function test_removing_from_cart_tracks_produto_removido_carrinho(): void
    {
        Bus::fake();

        $buyer = User::factory()->create();
        $expositor = $this->makeExpositor();
        $product = $this->makeProduct($expositor);

        $item = CartItem::create([
            'session_id' => 'test-session',
            'user_id' => $buyer->id,
            'product_id' => $product->id,
            'expositor_id' => $expositor->id,
            'quantity' => 3,
            'price_snapshot' => $product->price,
        ]);

        $this->actingAs($buyer);
        app(CartService::class)->remove($item->id);

        $this->assertEventTracked('produto.removido_carrinho', fn (array $props) => $props['produto_id'] === $product->id
            && $props['quantidade'] === 3);
    }

    // ─── checkout ─────────────────────────────────────────────────────────

    public function test_confirming_checkout_tracks_carrinho_iniciado_e_pedido_criado(): void
    {
        Bus::fake();

        $buyer = User::factory()->create();
        $expositor = $this->makeExpositor();
        $product = $this->makeProduct($expositor);

        CartItem::create([
            'session_id' => 'buyer-session',
            'user_id' => $buyer->id,
            'product_id' => $product->id,
            'expositor_id' => $expositor->id,
            'quantity' => 1,
            'price_snapshot' => $product->price,
        ]);

        Livewire::actingAs($buyer)
            ->test(Checkout::class)
            ->set('customer_name', 'Maria Compradora')
            ->set('customer_whatsapp', '(11)99999-9999')
            ->set('delivery_type', 'retirada')
            ->call('confirmar');

        $this->assertEventTracked('carrinho.checkout_iniciado');
        $this->assertEventTracked('pedido.criado', fn (array $props) => $props['valor_total'] === 89.9);
    }

    // ─── pagamento confirmado ─────────────────────────────────────────────

    public function test_confirming_split_tracks_pedido_pagamento_confirmado(): void
    {
        Bus::fake();

        $buyer = User::factory()->create();
        $expositor = $this->makeExpositor();
        $product = $this->makeProduct($expositor);

        $order = Order::create([
            'user_id' => $buyer->id,
            'reference' => 'TEST-'.uniqid(),
            'status' => 'aguardando_pagamento',
            'delivery_type' => 'retirada',
            'customer_name' => $buyer->name,
            'customer_whatsapp' => '11999990000',
            'items_total' => $product->price,
            'shipping_total' => 0,
            'total_amount' => $product->price,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'expositor_id' => $expositor->id,
            'product_name' => $product->name,
            'unit_price' => $product->price,
            'quantity' => 1,
            'total_price' => $product->price,
        ]);

        $split = OrderSplit::create([
            'order_id' => $order->id,
            'expositor_id' => $expositor->id,
            'gross_amount' => $product->price,
            'commission_percent' => 10,
            'commission_amount' => 8.99,
            'net_amount' => 80.91,
            'status' => 'pendente',
        ]);

        $split->confirmar();

        $this->assertEventTracked('pedido.pagamento_confirmado', fn (array $props) => $props['pedido_id'] === $order->id
            && $props['split_id'] === $split->id);
    }

    // ─── pedido enviado ───────────────────────────────────────────────────

    public function test_marking_split_as_shipped_tracks_pedido_enviado(): void
    {
        Bus::fake();

        $buyer = User::factory()->create();
        $expositor = $this->makeExpositor();
        $product = $this->makeProduct($expositor);
        $lojista = $expositor->user;

        $order = Order::create([
            'user_id' => $buyer->id,
            'reference' => 'TEST-'.uniqid(),
            'status' => 'pagamento_confirmado',
            'delivery_type' => 'entrega',
            'customer_name' => $buyer->name,
            'customer_email' => $buyer->email,
            'customer_whatsapp' => '11999990000',
            'items_total' => $product->price,
            'shipping_total' => 0,
            'total_amount' => $product->price,
        ]);

        $split = OrderSplit::create([
            'order_id' => $order->id,
            'expositor_id' => $expositor->id,
            'gross_amount' => $product->price,
            'commission_percent' => 10,
            'commission_amount' => 8.99,
            'net_amount' => 80.91,
            'status' => 'confirmado',
            'confirmed_at' => now(),
        ]);

        Livewire::actingAs($lojista)
            ->test(PedidoIndex::class)
            ->call('openShipModal', $split->id)
            ->set('carrier', 'Correios')
            ->set('trackingCode', 'BR123456789')
            ->set('shippedAtDate', now()->format('Y-m-d'))
            ->call('markAsShipped');

        $this->assertEventTracked('pedido.enviado', fn (array $props) => $props['pedido_id'] === $order->id
            && $props['split_id'] === $split->id
            && $props['transportadora'] === 'Correios');
    }

    // ─── proteção contra falhas do SDK ──────────────────────────────────────

    public function test_tracking_failure_does_not_break_add_to_cart(): void
    {
        // Sem Bus::fake() aqui: o Job roda de verdade (QUEUE_CONNECTION=sync
        // em testes), mas a chamada HTTP real é substituída por uma falha
        // simulada via Http::fake(). Isso exercita o try/catch do
        // CartService ponta a ponta — o carrinho precisa continuar
        // funcionando mesmo se a API JMF CI estiver fora do ar.
        Http::fake([
            '*' => Http::response('Service Unavailable', 500),
        ]);

        $expositor = $this->makeExpositor();
        $product = $this->makeProduct($expositor);

        app(CartService::class)->add($product, 1);

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);
    }
}
