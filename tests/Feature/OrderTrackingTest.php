<?php

namespace Tests\Feature;

use App\Enums\OrderSplitStatus;
use App\Enums\OrderStatus;
use App\Enums\ShippingStatus;
use App\Enums\TrackingEventSource;
use App\Enums\UserRole;
use App\Jobs\TrackShipmentsJob;
use App\Livewire\Lojista\Pedidos\PedidoIndex;
use App\Mail\ShipmentShippedMail;
use App\Models\Expositor;
use App\Models\Order;
use App\Models\OrderShipping;
use App\Models\OrderSplit;
use App\Models\OrderTrackingEvent;
use App\Services\Shipping\MelhorEnvioService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class OrderTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function makeLojista(): \App\Models\User
    {
        $user = \App\Models\User::factory()->create(['role' => UserRole::Lojista]);
        $user->assignRole('lojista');

        return $user;
    }

    private function makeOrderWithSplit(\App\Models\User $lojista): array
    {
        $expositor = Expositor::create([
            'user_id' => $lojista->id,
            'name'    => 'Loja Teste',
            'slug'    => 'loja-teste-' . uniqid(),
        ]);

        $order = Order::create([
            'reference'          => 'TEST-' . strtoupper(uniqid()),
            'customer_name'      => 'Maria Teste',
            'customer_whatsapp'  => '11999999999',
            'customer_email'     => 'cliente@example.com',
            'status'             => OrderStatus::PagamentoConfirmado,
            'items_total'        => 100,
            'shipping_total'     => 0,
            'total_amount'       => 100,
            'delivery_type'      => 'retirada',
        ]);

        $split = OrderSplit::create([
            'order_id'           => $order->id,
            'expositor_id'       => $expositor->id,
            'gross_amount'       => 100,
            'commission_percent' => 10,
            'commission_amount'  => 10,
            'net_amount'         => 90,
            'status'             => OrderSplitStatus::Confirmado,
        ]);

        return [$order, $split, $expositor];
    }

    // ─── Testes de modelo ─────────────────────────────────────────────────────

    public function test_order_shipping_can_be_created_and_related_to_split(): void
    {
        $lojista = $this->makeLojista();
        [$order, $split, $expositor] = $this->makeOrderWithSplit($lojista);

        $shipping = OrderShipping::create([
            'order_id'       => $order->id,
            'order_split_id' => $split->id,
            'expositor_id'   => $expositor->id,
            'carrier'        => 'Correios',
            'tracking_code'  => 'BR123456789BR',
            'status'         => ShippingStatus::Shipped,
            'shipped_at'     => now(),
        ]);

        $this->assertSame($order->id, $shipping->order->id);
        $this->assertSame($split->id, $shipping->split->id);
        $this->assertSame($expositor->id, $shipping->expositor->id);
        $this->assertSame(ShippingStatus::Shipped, $shipping->status);
    }

    public function test_order_split_has_one_shipping(): void
    {
        $lojista = $this->makeLojista();
        [$order, $split, $expositor] = $this->makeOrderWithSplit($lojista);

        $this->assertNull($split->shipping);

        OrderShipping::create([
            'order_id'       => $order->id,
            'order_split_id' => $split->id,
            'expositor_id'   => $expositor->id,
            'status'         => ShippingStatus::Shipped,
            'shipped_at'     => now(),
        ]);

        $this->assertNotNull($split->fresh()->shipping);
    }

    public function test_tracking_event_can_be_added_via_helper(): void
    {
        $lojista = $this->makeLojista();
        [$order, $split, $expositor] = $this->makeOrderWithSplit($lojista);

        $shipping = OrderShipping::create([
            'order_id'       => $order->id,
            'order_split_id' => $split->id,
            'expositor_id'   => $expositor->id,
            'status'         => ShippingStatus::Shipped,
            'shipped_at'     => now(),
        ]);

        $event = $shipping->addEvent(
            ShippingStatus::InTransit->value,
            'Objeto em triagem na unidade de Curitiba.',
            'Curitiba, PR',
        );

        $this->assertInstanceOf(OrderTrackingEvent::class, $event);
        $this->assertSame(TrackingEventSource::Manual, $event->source);
        $this->assertSame('Curitiba, PR', $event->location);
        $this->assertCount(1, $shipping->trackingEvents);
    }

    public function test_carrier_tracking_url_uses_correios_format(): void
    {
        $shipping = new OrderShipping([
            'carrier'       => 'Correios',
            'tracking_code' => 'AA123456789BR',
        ]);

        $this->assertStringContainsString('correios', $shipping->carrierTrackingUrl());
        $this->assertStringContainsString('AA123456789BR', $shipping->carrierTrackingUrl());
    }

    public function test_estimated_delivery_date_adds_weekdays_to_shipped_at(): void
    {
        $shipping = new OrderShipping([
            'shipped_at'     => now()->startOfDay(),
            'estimated_days' => 5,
        ]);

        $estimated = $shipping->estimatedDeliveryDate();

        $this->assertNotNull($estimated);
        // 5 dias úteis > 5 dias corridos
        $this->assertTrue($estimated->greaterThan(now()->addDays(4)));
    }

    // ─── Testes de lojista UI ─────────────────────────────────────────────────

    public function test_lojista_can_open_ship_modal_for_confirmed_split(): void
    {
        $lojista = $this->makeLojista();
        [$order, $split, $expositor] = $this->makeOrderWithSplit($lojista);

        Livewire::actingAs($lojista)
            ->test(PedidoIndex::class)
            ->call('openShipModal', $split->id)
            ->assertSet('showShipModal', true)
            ->assertSet('shipSplitId', $split->id);
    }

    public function test_lojista_mark_as_shipped_creates_shipping_and_event(): void
    {
        Mail::fake();
        $lojista = $this->makeLojista();
        [$order, $split, $expositor] = $this->makeOrderWithSplit($lojista);

        Livewire::actingAs($lojista)
            ->test(PedidoIndex::class)
            ->call('openShipModal', $split->id)
            ->set('carrier', 'Correios')
            ->set('trackingCode', 'BR000111222BR')
            ->set('shippedAtDate', now()->format('Y-m-d'))
            ->call('markAsShipped');

        $shipping = $split->fresh()->shipping;

        $this->assertNotNull($shipping);
        $this->assertSame('BR000111222BR', $shipping->tracking_code);
        $this->assertSame(ShippingStatus::Shipped, $shipping->status);
        $this->assertCount(1, $shipping->trackingEvents);

        Mail::assertSent(ShipmentShippedMail::class, fn ($mail) =>
            $mail->hasTo('cliente@example.com')
        );
    }

    public function test_mark_as_shipped_requires_carrier_and_tracking_code(): void
    {
        $lojista = $this->makeLojista();
        [$order, $split] = $this->makeOrderWithSplit($lojista);

        Livewire::actingAs($lojista)
            ->test(PedidoIndex::class)
            ->call('openShipModal', $split->id)
            ->set('carrier', '')
            ->set('trackingCode', '')
            ->call('markAsShipped')
            ->assertHasErrors(['carrier', 'trackingCode']);
    }

    // ─── Testes de página pública de rastreio ─────────────────────────────────

    public function test_public_tracking_page_loads_with_valid_code(): void
    {
        $lojista = $this->makeLojista();
        [$order, $split, $expositor] = $this->makeOrderWithSplit($lojista);

        $shipping = OrderShipping::create([
            'order_id'       => $order->id,
            'order_split_id' => $split->id,
            'expositor_id'   => $expositor->id,
            'carrier'        => 'Jadlog',
            'tracking_code'  => 'JDL999888777',
            'status'         => ShippingStatus::InTransit,
            'shipped_at'     => now()->subDays(2),
        ]);

        $this->get(route('rastreio.show', 'JDL999888777'))
            ->assertOk()
            ->assertSee('JDL999888777')
            ->assertSee($expositor->name);
    }

    public function test_public_tracking_page_returns_404_for_invalid_code(): void
    {
        $this->get(route('rastreio.show', 'INVALIDO123'))
            ->assertNotFound();
    }

    // ─── Testes do job de sincronização ──────────────────────────────────────

    public function test_track_shipments_job_skips_terminal_statuses(): void
    {
        $lojista = $this->makeLojista();
        [$order, $split, $expositor] = $this->makeOrderWithSplit($lojista);

        $delivered = OrderShipping::create([
            'order_id'       => $order->id,
            'order_split_id' => $split->id,
            'expositor_id'   => $expositor->id,
            'tracking_code'  => 'ENTREGUE123',
            'status'         => ShippingStatus::Delivered,
            'shipped_at'     => now()->subDays(5),
            'delivered_at'   => now()->subDay(),
        ]);

        $service = $this->createMock(MelhorEnvioService::class);
        $service->expects($this->never())->method('track');

        $job = new TrackShipmentsJob();
        $job->handle($service);

        // Status permanece intacto
        $this->assertSame(ShippingStatus::Delivered, $delivered->fresh()->status);
    }

    public function test_track_shipments_job_marks_order_as_concluido_when_all_delivered(): void
    {
        $lojista = $this->makeLojista();
        [$order, $split, $expositor] = $this->makeOrderWithSplit($lojista);

        $shipping = OrderShipping::create([
            'order_id'       => $order->id,
            'order_split_id' => $split->id,
            'expositor_id'   => $expositor->id,
            'tracking_code'  => 'TEST001',
            'status'         => ShippingStatus::OutForDelivery,
            'shipped_at'     => now()->subDays(3),
        ]);

        $service = $this->createMock(MelhorEnvioService::class);
        $service->method('track')->willReturn([[
            'status'      => 'delivered',
            'description' => 'Objeto entregue ao destinatário.',
            'location'    => 'São Paulo, SP',
            'happened_at' => now()->toDateTimeString(),
        ]]);

        (new TrackShipmentsJob())->handle($service);

        $this->assertSame(ShippingStatus::Delivered, $shipping->fresh()->status);
        $this->assertSame(OrderStatus::Concluido, $order->fresh()->status);
    }
}
