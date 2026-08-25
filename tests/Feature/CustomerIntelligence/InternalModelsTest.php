<?php

namespace Tests\Feature\CustomerIntelligence;

use App\CustomerIntelligence\Enums\EventName;
use App\CustomerIntelligence\Models\DailyMetric;
use App\CustomerIntelligence\Models\TrackedEvent;
use App\CustomerIntelligence\Models\Visitor;
use App\CustomerIntelligence\Models\VisitorSession;
use App\Models\Expositor;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Models do modulo interno (CI-02): UUIDs, casts e relacionamentos.
 */
class InternalModelsTest extends TestCase
{
    use RefreshDatabase;

    private function makeVisitor(?User $user = null): Visitor
    {
        return Visitor::create([
            'user_id' => $user?->id,
            'first_seen_at' => now(),
            'last_seen_at' => now(),
        ]);
    }

    private function makeSession(Visitor $visitor): VisitorSession
    {
        return VisitorSession::create([
            'visitor_id' => $visitor->id,
            'started_at' => now(),
        ]);
    }

    private function makeProduct(): Product
    {
        $expositor = Expositor::create(['name' => 'Loja CI-02', 'slug' => 'loja-ci-02']);

        return Product::create([
            'expositor_id' => $expositor->id,
            'item_type' => 'produto',
            'name' => 'Cesta de Palha',
            'slug' => 'cesta-de-palha',
            'price' => 49.90,
            'is_active' => true,
        ]);
    }

    // ─── UUIDs ────────────────────────────────────────────────────────────

    public function test_uuids_are_generated_while_the_primary_key_stays_auto_incrementing(): void
    {
        $visitor = $this->makeVisitor();
        $session = $this->makeSession($visitor);
        $event = TrackedEvent::create([
            'event_name' => EventName::ProdutoVisualizado,
            'occurred_at' => now(),
        ]);

        foreach ([$visitor->visitor_uuid, $session->session_uuid, $event->event_uuid] as $uuid) {
            $this->assertNotNull($uuid);
            $this->assertMatchesRegularExpression(
                '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
                $uuid
            );
        }

        // A chave interna continua sendo o inteiro auto incremental.
        $this->assertIsInt($visitor->id);
        $this->assertTrue($visitor->getIncrementing());
        $this->assertSame('int', $visitor->getKeyType());
        $this->assertSame('id', $visitor->getKeyName());
    }

    public function test_a_provided_uuid_is_preserved(): void
    {
        $uuid = '0192f1a0-1111-7222-8333-444455556666';

        $visitor = Visitor::create(['visitor_uuid' => $uuid, 'first_seen_at' => now()]);

        $this->assertSame($uuid, $visitor->fresh()->visitor_uuid);
    }

    // ─── Casts ────────────────────────────────────────────────────────────

    public function test_json_and_date_casts(): void
    {
        $visitor = Visitor::create([
            'first_seen_at' => '2026-08-01 10:00:00',
            'metadata' => ['primeira_origem' => 'instagram'],
        ]);

        $event = TrackedEvent::create([
            'event_name' => EventName::PedidoCriado,
            'properties' => ['valor_total' => 120.5, 'itens' => 3],
            'occurred_at' => '2026-08-02 15:30:00',
        ]);

        $visitor = $visitor->fresh();
        $event = $event->fresh();

        $this->assertSame(['primeira_origem' => 'instagram'], $visitor->metadata);
        $this->assertInstanceOf(Carbon::class, $visitor->first_seen_at);

        $this->assertSame(['valor_total' => 120.5, 'itens' => 3], $event->properties);
        $this->assertInstanceOf(Carbon::class, $event->occurred_at);
        $this->assertSame('2026-08-02 15:30:00', $event->occurred_at->toDateTimeString());
    }

    public function test_event_name_is_cast_to_the_enum(): void
    {
        $event = TrackedEvent::create([
            'event_name' => EventName::PedidoEnviado,
            'occurred_at' => now(),
        ]);

        $this->assertInstanceOf(EventName::class, $event->fresh()->event_name);
        $this->assertSame('pedido.enviado', $event->fresh()->event_name->value);
    }

    public function test_daily_metric_casts_date_and_value(): void
    {
        $metric = DailyMetric::record('2026-08-25', 'visitantes', 42.5)->fresh();

        $this->assertSame('2026-08-25', $metric->metric_date->toDateString());
        $this->assertSame('42.5000', $metric->metric_value);
    }

    // ─── Relacionamentos ──────────────────────────────────────────────────

    public function test_visitor_has_many_sessions(): void
    {
        $visitor = $this->makeVisitor();
        $this->makeSession($visitor);
        $this->makeSession($visitor);

        $this->assertCount(2, $visitor->sessions);
        $this->assertTrue($visitor->sessions->first()->visitor->is($visitor));
    }

    public function test_visitor_and_session_have_many_events(): void
    {
        $visitor = $this->makeVisitor();
        $session = $this->makeSession($visitor);

        TrackedEvent::create([
            'visitor_id' => $visitor->id,
            'session_id' => $session->id,
            'event_name' => EventName::ProdutoVisualizado,
            'occurred_at' => now(),
        ]);

        $this->assertCount(1, $visitor->events);
        $this->assertCount(1, $session->events);
        $this->assertTrue($session->events->first()->session->is($session));
        $this->assertTrue($visitor->events->first()->visitor->is($visitor));
    }

    public function test_visitor_belongs_to_user_when_identified(): void
    {
        $user = User::factory()->create();

        $anonymous = $this->makeVisitor();
        $identified = $this->makeVisitor($user);

        $this->assertFalse($anonymous->isIdentified());
        $this->assertNull($anonymous->user);

        $this->assertTrue($identified->isIdentified());
        $this->assertTrue($identified->user->is($user));
    }

    public function test_event_points_to_a_domain_entity_through_a_morph(): void
    {
        $product = $this->makeProduct();

        $event = TrackedEvent::create([
            'event_name' => EventName::ProdutoVisualizado,
            'entity_type' => $product->getMorphClass(),
            'entity_id' => $product->id,
            'occurred_at' => now(),
        ]);

        $this->assertTrue($event->fresh()->entity->is($product));
    }

    public function test_session_reports_whether_it_is_open(): void
    {
        $session = $this->makeSession($this->makeVisitor());
        $this->assertTrue($session->isOpen());

        $session->update(['ended_at' => now()]);
        $this->assertFalse($session->fresh()->isOpen());
    }

    // ─── Enum ─────────────────────────────────────────────────────────────

    public function test_event_name_enum_covers_the_seven_events_currently_tracked(): void
    {
        $expected = [
            'produto.visualizado',
            'produto.adicionado_carrinho',
            'produto.removido_carrinho',
            'carrinho.checkout_iniciado',
            'pedido.criado',
            'pedido.pagamento_confirmado',
            'pedido.enviado',
        ];

        $this->assertSame($expected, array_column(EventName::cases(), 'value'));
    }

    public function test_event_name_derives_its_category_from_the_prefix(): void
    {
        $this->assertSame('produto', EventName::ProdutoVisualizado->category());
        $this->assertSame('carrinho', EventName::CarrinhoCheckoutIniciado->category());
        $this->assertSame('pedido', EventName::PedidoPagamentoConfirmado->category());
    }
}
