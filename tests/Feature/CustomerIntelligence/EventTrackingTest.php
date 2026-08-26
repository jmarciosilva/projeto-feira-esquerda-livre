<?php

namespace Tests\Feature\CustomerIntelligence;

use App\CustomerIntelligence\Enums\EventName;
use App\CustomerIntelligence\Facades\CustomerIntelligence;
use App\CustomerIntelligence\Models\TrackedEvent;
use App\CustomerIntelligence\Services\CustomerIntelligenceService;
use App\Enums\UserRole;
use App\Livewire\Checkout;
use App\Livewire\Lojista\Pedidos\PedidoIndex;
use App\Models\CartItem;
use App\Models\Expositor;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderSplit;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use RuntimeException;
use Tests\Concerns\InteractsWithConsent;
use Tests\TestCase;

/**
 * Os sete eventos de negocio rastreados pela plataforma.
 *
 * Desde a CI-05 estes eventos sao gravados pelo MODULO INTERNO, direto em
 * `ci_events`. Antes iam por HTTP para a plataforma externa; estes testes
 * verificavam o despacho do `SendPayloadJob` do SDK e passaram a verificar o
 * que realmente interessa: a linha que ficou no banco.
 *
 * Em testes `QUEUE_CONNECTION=sync`, entao `track()` enfileira e o job e
 * processado na hora — o caminho inteiro e exercitado.
 */
class EventTrackingTest extends TestCase
{
    use InteractsWithConsent, RefreshDatabase;

    /**
     * Analytics e opt-in desde a GOV-01. Esta suite descreve o comportamento da
     * COLETA, que so existe sob aceite — entao o aceite e a precondicao dela.
     * O que acontece sem aceite tem suite propria: ConsentPolicyTest.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->acceptingAnalytics();
    }

    private function makeExpositor(): Expositor
    {
        static $counter = 0;
        $counter++;

        $expositor = Expositor::create(['name' => "Loja CI {$counter}", 'slug' => "loja-ci-{$counter}"]);
        $expositor->update(['user_id' => User::factory()->create(['role' => UserRole::Lojista])->id]);

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

    /**
     * @param  callable(array<string, mixed>): bool|null  $propertiesCallback
     */
    private function assertEventTracked(EventName $event, ?callable $propertiesCallback = null): TrackedEvent
    {
        $tracked = TrackedEvent::where('event_name', $event->value)->get();

        $this->assertNotEmpty(
            $tracked,
            "Esperava o evento {$event->value} gravado em ci_events, mas ele não foi registrado."
        );

        if ($propertiesCallback === null) {
            return $tracked->first();
        }

        $match = $tracked->first(fn (TrackedEvent $e) => $propertiesCallback($e->properties ?? []));

        $this->assertNotNull(
            $match,
            "O evento {$event->value} foi gravado, mas com propriedades diferentes das esperadas."
        );

        return $match;
    }

    // ─── produto.visualizado ──────────────────────────────────────────────

    public function test_viewing_product_page_tracks_produto_visualizado(): void
    {
        $expositor = $this->makeExpositor();
        $product = $this->makeProduct($expositor);

        $this->get(route('loja.produto', [$expositor->slug, $product->slug]))->assertOk();

        $event = $this->assertEventTracked(
            EventName::ProdutoVisualizado,
            fn (array $props) => $props['produto_id'] === $product->id
                && $props['expositor_id'] === $expositor->id
        );

        // O produto deixou de viver apenas dentro do JSON: virou referência.
        $this->assertSame($product->getMorphClass(), $event->entity_type);
        $this->assertSame($product->id, $event->entity_id);

        // A visita web resolve visitante e sessão pelo middleware.
        $this->assertNotNull($event->visitor_id);
        $this->assertNotNull($event->session_id);
    }

    // ─── carrinho ─────────────────────────────────────────────────────────

    public function test_adding_to_cart_tracks_produto_adicionado_carrinho(): void
    {
        $expositor = $this->makeExpositor();
        $product = $this->makeProduct($expositor);

        app(CartService::class)->add($product, 2);

        $event = $this->assertEventTracked(
            EventName::ProdutoAdicionadoCarrinho,
            fn (array $props) => $props['produto_id'] === $product->id
                && $props['quantidade'] === 2
        );

        $this->assertSame($product->id, $event->entity_id);
    }

    public function test_removing_from_cart_tracks_produto_removido_carrinho(): void
    {
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

        $this->assertEventTracked(
            EventName::ProdutoRemovidoCarrinho,
            fn (array $props) => $props['produto_id'] === $product->id
                && $props['quantidade'] === 3
        );
    }

    // ─── checkout ─────────────────────────────────────────────────────────

    public function test_confirming_checkout_tracks_carrinho_iniciado_e_pedido_criado(): void
    {
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

        $this->assertEventTracked(EventName::CarrinhoCheckoutIniciado);

        $pedido = $this->assertEventTracked(
            EventName::PedidoCriado,
            fn (array $props) => $props['valor_total'] === 89.9
        );

        $this->assertSame(Order::sole()->id, $pedido->entity_id);
        $this->assertSame($buyer->id, $pedido->user_id);
    }

    // ─── pagamento confirmado ─────────────────────────────────────────────

    public function test_confirming_split_tracks_pedido_pagamento_confirmado(): void
    {
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

        $event = $this->assertEventTracked(
            EventName::PedidoPagamentoConfirmado,
            fn (array $props) => $props['pedido_id'] === $order->id
                && $props['split_id'] === $split->id
        );

        $this->assertSame($split->id, $event->entity_id);
    }

    // ─── pedido enviado ───────────────────────────────────────────────────

    public function test_marking_split_as_shipped_tracks_pedido_enviado(): void
    {
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

        $this->assertEventTracked(
            EventName::PedidoEnviado,
            fn (array $props) => $props['pedido_id'] === $order->id
                && $props['split_id'] === $split->id
                && $props['transportadora'] === 'Correios'
        );
    }

    // ─── garantias da migração ────────────────────────────────────────────

    public function test_the_seven_events_no_longer_leave_the_application(): void
    {
        // Nada de rede: se alguma chamada tentasse sair, o teste falharia.
        Http::preventStrayRequests();

        $expositor = $this->makeExpositor();
        $product = $this->makeProduct($expositor);

        $this->get(route('loja.produto', [$expositor->slug, $product->slug]))->assertOk();
        app(CartService::class)->add($product, 1);

        $this->assertSame(2, TrackedEvent::count());
    }

    public function test_tracking_failure_does_not_break_add_to_cart(): void
    {
        // O módulo interno fora do ar não pode derrubar o carrinho.
        $this->mock(
            CustomerIntelligenceService::class,
            fn ($mock) => $mock->shouldReceive('track')->andThrow(new RuntimeException('analytics indisponível'))
        );
        CustomerIntelligence::clearResolvedInstances();

        $expositor = $this->makeExpositor();
        $product = $this->makeProduct($expositor);

        app(CartService::class)->add($product, 1);

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $product->id,
            'quantity' => 1,
        ]);
        $this->assertSame(0, TrackedEvent::count());
    }

    public function test_order_creation_survives_a_tracking_failure(): void
    {
        $this->mock(
            CustomerIntelligenceService::class,
            fn ($mock) => $mock->shouldReceive('track')->andThrow(new RuntimeException('analytics indisponível'))
        );
        CustomerIntelligence::clearResolvedInstances();

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

        $this->actingAs($buyer);
        $order = app(OrderService::class)->createFromCart([
            'customer_name' => 'Maria Compradora',
            'customer_whatsapp' => '11999990000',
            'delivery_type' => 'retirada',
        ], app(CartService::class));

        $this->assertNotNull($order->id);
        $this->assertSame(0, TrackedEvent::count());
    }
}
