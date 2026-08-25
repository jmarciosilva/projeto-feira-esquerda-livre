<?php

namespace Tests\Feature\CustomerIntelligence;

use App\CustomerIntelligence\Actions\ResolveVisitorSession;
use App\CustomerIntelligence\Enums\EventName;
use App\CustomerIntelligence\Enums\MetricName;
use App\CustomerIntelligence\Models\DailyMetric;
use App\CustomerIntelligence\Models\TrackedEvent;
use App\CustomerIntelligence\Models\Visitor;
use App\CustomerIntelligence\Models\VisitorSession;
use App\CustomerIntelligence\Services\CustomerIntelligenceService;
use App\Enums\UserRole;
use App\Livewire\Admin\CustomerIntelligence\Dashboard;
use App\Livewire\Admin\CustomerIntelligence\EventIndex;
use App\Livewire\Admin\CustomerIntelligence\VisitorIndex;
use App\Livewire\Admin\CustomerIntelligence\VisitorShow;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Painel administrativo lendo exclusivamente o banco local (CI-06).
 *
 * Cada teste comeca bloqueando qualquer requisicao HTTP: se algum componente
 * ainda tentasse falar com a plataforma externa, o teste falharia.
 */
class InternalPanelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // A garantia central desta fase: painel sem rede.
        Http::preventStrayRequests();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::Admin]);
    }

    private function service(): CustomerIntelligenceService
    {
        return app(CustomerIntelligenceService::class);
    }

    private function sessionFor(string $visitorUuid, ?User $user = null): VisitorSession
    {
        return app(ResolveVisitorSession::class)($visitorUuid, 'sessao-'.$visitorUuid, $user?->id);
    }

    // ─── Dashboard ────────────────────────────────────────────────────────

    public function test_dashboard_renders_with_no_data_at_all(): void
    {
        Livewire::actingAs($this->admin())
            ->test(Dashboard::class)
            ->assertOk()
            ->assertSee('Eventos')
            ->assertSee('Visitantes')
            ->assertSee('Sessões')
            ->assertSee('Conversões');
    }

    public function test_dashboard_counts_come_from_the_daily_aggregates(): void
    {
        $session = $this->sessionFor('v-dash');

        $this->service()->record(EventName::ProdutoVisualizado, session: $session);
        $this->service()->record(EventName::ProdutoVisualizado, session: $session);
        $this->service()->record(EventName::PedidoCriado, session: $session);

        $component = Livewire::actingAs($this->admin())->test(Dashboard::class);
        $metrics = $component->viewData('metrics');

        $this->assertSame(3, $metrics['events']);
        $this->assertSame(1, $metrics['conversions'], 'pedido.criado conta como conversão.');
        $this->assertSame(1, $metrics['sessions']);
        $this->assertSame(1, $metrics['visitors']);
    }

    public function test_dashboard_respects_the_selected_period(): void
    {
        $session = $this->sessionFor('v-periodo');

        $this->service()->record(EventName::ProdutoVisualizado, session: $session, occurredAt: Carbon::now()->subDays(45));
        $this->service()->record(EventName::ProdutoVisualizado, session: $session);

        $sete = Livewire::actingAs($this->admin())->test(Dashboard::class)->viewData('metrics');
        $this->assertSame(1, $sete['events'], 'Em 7 dias, só o evento recente.');

        $noventa = Livewire::actingAs($this->admin())
            ->test(Dashboard::class)
            ->call('setPeriod', '90')
            ->viewData('metrics');
        $this->assertSame(2, $noventa['events'], 'Em 90 dias, os dois.');
    }

    public function test_dashboard_trend_is_a_daily_series(): void
    {
        $session = $this->sessionFor('v-trend');
        $this->service()->record(EventName::ProdutoVisualizado, session: $session, occurredAt: Carbon::now()->subDays(2));
        $this->service()->record(EventName::ProdutoVisualizado, session: $session);
        $this->service()->record(EventName::PedidoEnviado, session: $session);

        $trend = Livewire::actingAs($this->admin())->test(Dashboard::class)->viewData('metrics')['trend'];

        $this->assertCount(2, $trend, 'Dois dias com eventos.');
        $this->assertSame(2, array_values($trend)[1], 'O dia de hoje somou 2.');
    }

    public function test_dashboard_lists_recent_visitors_and_events(): void
    {
        $user = User::factory()->create(['email' => 'cliente@teste.com']);
        $session = $this->sessionFor('v-recentes', $user);
        $this->service()->record(EventName::PedidoCriado, session: $session);

        $component = Livewire::actingAs($this->admin())->test(Dashboard::class);

        $visitantes = $component->viewData('recentContacts');
        $this->assertCount(1, $visitantes);
        $this->assertSame('cliente@teste.com', $visitantes[0]['email']);
        $this->assertSame(1, $visitantes[0]['events_count']);

        $eventos = $component->viewData('recentEvents');
        $this->assertSame('pedido.criado', $eventos[0]['event_name']);
        $this->assertSame('cliente@teste.com', $eventos[0]['contact_email']);
    }

    // ─── Eventos ──────────────────────────────────────────────────────────

    public function test_event_list_reads_ci_events(): void
    {
        $session = $this->sessionFor('v-lista');
        $this->service()->record(EventName::ProdutoVisualizado, ['produto_id' => 9], session: $session);

        Livewire::actingAs($this->admin())
            ->test(EventIndex::class)
            ->assertOk()
            ->assertSee('produto.visualizado');
    }

    public function test_event_list_filters_by_event_name(): void
    {
        $session = $this->sessionFor('v-filtro');
        $this->service()->record(EventName::ProdutoVisualizado, session: $session);
        $this->service()->record(EventName::PedidoCriado, session: $session);

        $component = Livewire::actingAs($this->admin())
            ->test(EventIndex::class)
            ->set('eventName', 'pedido.');

        $this->assertSame(1, $component->viewData('total'));
        $this->assertSame('pedido.criado', $component->viewData('events')[0]['event_name']);
    }

    public function test_event_list_filters_by_visitor(): void
    {
        $a = $this->sessionFor('alpha');
        $b = $this->sessionFor('beta');
        $this->service()->record(EventName::ProdutoVisualizado, session: $a);
        $this->service()->record(EventName::PedidoCriado, session: $b);

        $component = Livewire::actingAs($this->admin())
            ->test(EventIndex::class)
            ->set('search', 'alpha');

        $this->assertSame(1, $component->viewData('total'));
    }

    public function test_event_list_paginates_in_the_database(): void
    {
        $session = $this->sessionFor('v-pagina');

        for ($i = 0; $i < 60; $i++) {
            $this->service()->record(EventName::ProdutoVisualizado, session: $session);
        }

        $component = Livewire::actingAs($this->admin())->test(EventIndex::class);

        $this->assertSame(60, $component->viewData('total'));
        $this->assertCount(50, $component->viewData('events'), 'A página traz no máximo o perPage.');
    }

    // ─── Visitantes ───────────────────────────────────────────────────────

    public function test_visitor_list_shows_anonymous_and_identified(): void
    {
        $user = User::factory()->create(['email' => 'maria@teste.com', 'name' => 'Maria']);
        $this->sessionFor('anonimo');
        $this->sessionFor('identificado', $user);

        $component = Livewire::actingAs($this->admin())->test(VisitorIndex::class);
        $contatos = collect($component->viewData('contacts'));

        $this->assertSame(2, $component->viewData('total'));
        $this->assertTrue($contatos->contains(fn ($c) => $c['email'] === 'maria@teste.com' && $c['name'] === 'Maria'));
        $this->assertTrue($contatos->contains(fn ($c) => $c['email'] === null), 'Visitante anônimo não inventa e-mail.');
    }

    public function test_visitor_list_searches_by_uuid_and_email(): void
    {
        $user = User::factory()->create(['email' => 'joao@teste.com']);
        $this->sessionFor('procurado', $user);
        $this->sessionFor('outro');

        $porUuid = Livewire::actingAs($this->admin())->test(VisitorIndex::class)->set('search', 'procur');
        $this->assertSame(1, $porUuid->viewData('total'));

        $porEmail = Livewire::actingAs($this->admin())->test(VisitorIndex::class)->set('search', 'joao@');
        $this->assertSame(1, $porEmail->viewData('total'));
    }

    // ─── Detalhe ──────────────────────────────────────────────────────────

    public function test_visitor_detail_shows_local_data_and_timeline(): void
    {
        $user = User::factory()->create(['email' => 'detalhe@teste.com', 'name' => 'Cliente Detalhe']);
        $session = $this->sessionFor('v-detalhe', $user);
        $this->service()->record(EventName::ProdutoVisualizado, session: $session);
        $this->service()->record(EventName::PedidoCriado, session: $session);

        $component = Livewire::actingAs($this->admin())
            ->test(VisitorShow::class, ['visitor' => 'v-detalhe'])
            ->assertOk();

        $contact = $component->viewData('contact');
        $this->assertSame('v-detalhe', $contact['visitor_uuid']);
        $this->assertSame('detalhe@teste.com', $contact['email']);
        $this->assertSame(2, $contact['events_count']);
        $this->assertSame(1, $contact['sessions_count']);

        $this->assertCount(2, $component->viewData('events'));
    }

    public function test_visitor_detail_route_is_reachable_by_an_admin(): void
    {
        $this->sessionFor('v-rota');

        $this->actingAs($this->admin())
            ->get(route('admin.customer-intelligence.visitante', 'v-rota'))
            ->assertOk();
    }

    public function test_visitor_detail_requires_permission(): void
    {
        $this->sessionFor('v-proibido');
        $cliente = User::factory()->create(['role' => UserRole::User]);

        $this->actingAs($cliente)
            ->get(route('admin.customer-intelligence.visitante', 'v-proibido'))
            ->assertForbidden();
    }

    // ─── Agregação ────────────────────────────────────────────────────────

    public function test_aggregates_are_incremented_per_event_and_per_name(): void
    {
        $session = $this->sessionFor('v-agg');
        $this->service()->record(EventName::ProdutoVisualizado, session: $session);
        $this->service()->record(EventName::ProdutoVisualizado, session: $session);

        $hoje = Carbon::now()->toDateString();

        $total = DailyMetric::where('metric_name', MetricName::Eventos->value)
            ->where('dimension_type', '')->where('metric_date', $hoje)->value('metric_value');
        $porNome = DailyMetric::where('metric_name', MetricName::Eventos->value)
            ->where('dimension_type', MetricName::DIMENSION_EVENT_NAME)
            ->where('dimension_value', 'produto.visualizado')->value('metric_value');

        $this->assertSame('2.0000', $total);
        $this->assertSame('2.0000', $porNome);
    }

    public function test_a_visitor_is_counted_once_per_day_even_with_several_sessions(): void
    {
        $visitor = Visitor::create(['visitor_uuid' => 'v-multi', 'first_seen_at' => now()]);

        // Três sessões do mesmo visitante no mesmo dia.
        foreach (['s1', 's2', 's3'] as $uuid) {
            app(ResolveVisitorSession::class)('v-multi', $uuid);
        }

        $hoje = Carbon::now()->toDateString();
        $visitantes = DailyMetric::where('metric_name', MetricName::Visitantes->value)
            ->where('metric_date', $hoje)->value('metric_value');
        $sessoes = DailyMetric::where('metric_name', MetricName::Sessoes->value)
            ->where('metric_date', $hoje)->value('metric_value');

        $this->assertSame('1.0000', $visitantes, 'Visitantes distintos: um.');
        $this->assertSame('3.0000', $sessoes, 'Sessões: três.');
        $this->assertSame(1, Visitor::count());
    }

    public function test_the_aggregate_is_stored_on_the_day_the_event_happened(): void
    {
        $session = $this->sessionFor('v-ontem');
        $ontem = Carbon::now()->subDay();

        $this->service()->record(EventName::PedidoCriado, session: $session, occurredAt: $ontem);

        $this->assertDatabaseHas('ci_daily_metrics', [
            'metric_date' => $ontem->toDateString(),
            'metric_name' => MetricName::Conversoes->value,
        ]);
    }

    // ─── Reconstrução ─────────────────────────────────────────────────────

    public function test_rebuild_command_recreates_aggregates_from_events(): void
    {
        $session = $this->sessionFor('v-rebuild');
        $this->service()->record(EventName::ProdutoVisualizado, session: $session);
        $this->service()->record(EventName::PedidoCriado, session: $session);

        // Simula agregados perdidos ou nunca calculados.
        DailyMetric::query()->delete();
        $this->assertSame(0, DailyMetric::count());

        $this->artisan('customer-intelligence:rebuild-daily-metrics')->assertSuccessful();

        $hoje = Carbon::now()->toDateString();
        $this->assertSame(
            '2.0000',
            DailyMetric::where('metric_name', MetricName::Eventos->value)
                ->where('dimension_type', '')->where('metric_date', $hoje)->value('metric_value')
        );
        $this->assertSame(
            '1.0000',
            DailyMetric::where('metric_name', MetricName::Conversoes->value)->value('metric_value')
        );
    }

    public function test_rebuild_command_is_idempotent(): void
    {
        $session = $this->sessionFor('v-idem');
        $this->service()->record(EventName::ProdutoVisualizado, session: $session);

        $this->artisan('customer-intelligence:rebuild-daily-metrics')->assertSuccessful();
        $primeira = DailyMetric::orderBy('metric_name')->pluck('metric_value', 'metric_name')->toArray();

        $this->artisan('customer-intelligence:rebuild-daily-metrics')->assertSuccessful();
        $segunda = DailyMetric::orderBy('metric_name')->pluck('metric_value', 'metric_name')->toArray();

        $this->assertSame($primeira, $segunda, 'Rodar duas vezes não pode dobrar os valores.');
    }

    public function test_rebuild_command_never_touches_raw_events(): void
    {
        $session = $this->sessionFor('v-seguro');
        $this->service()->record(EventName::ProdutoVisualizado, session: $session);
        $antes = TrackedEvent::count();

        $this->artisan('customer-intelligence:rebuild-daily-metrics')->assertSuccessful();

        $this->assertSame($antes, TrackedEvent::count());
    }
}
