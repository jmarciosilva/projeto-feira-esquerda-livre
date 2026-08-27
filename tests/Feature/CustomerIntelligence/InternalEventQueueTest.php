<?php

namespace Tests\Feature\CustomerIntelligence;

use App\CustomerIntelligence\Actions\ResolveVisitorSession;
use App\CustomerIntelligence\Enums\EventName;
use App\CustomerIntelligence\Jobs\TrackCustomerEventJob;
use App\CustomerIntelligence\Models\TrackedEvent;
use App\CustomerIntelligence\Services\CustomerIntelligenceService;
use App\CustomerIntelligence\Support\VisitorContext;
use App\Models\Expositor;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;
use Tests\Concerns\InteractsWithConsent;
use Tests\TestCase;

/**
 * Escrita de eventos pela fila dedicada (CI-04).
 *
 * Nenhuma chamada da aplicacao usa este caminho ainda: os sete eventos atuais
 * continuam saindo pelo SDK externo ate a CI-05.
 */
class InternalEventQueueTest extends TestCase
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

    private function service(): CustomerIntelligenceService
    {
        return app(CustomerIntelligenceService::class);
    }

    private function makeProduct(): Product
    {
        $expositor = Expositor::create(['name' => 'Loja Fila', 'slug' => 'loja-fila']);

        return Product::factory()->create([
            'expositor_id' => $expositor->id,
            'item_type' => 'produto',
            'name' => 'Chapéu de Palha',
            'slug' => 'chapeu-de-palha',
            'price' => 59.90,
            'is_active' => true,
        ]);
    }

    // ─── Destino do job ───────────────────────────────────────────────────

    public function test_track_dispatches_the_job_to_the_module_queue(): void
    {
        Bus::fake();

        $this->service()->track(EventName::ProdutoVisualizado, ['produto_id' => 7]);

        Bus::assertDispatched(
            TrackCustomerEventJob::class,
            fn (TrackCustomerEventJob $job) => $job->event === EventName::ProdutoVisualizado
                && $job->properties === ['produto_id' => 7]
                && $job->queue === 'customer-intelligence'
        );
    }

    /**
     * O destino vale mesmo quando o job e despachado direto, sem passar pelo
     * service — por isso a fila e definida no construtor do proprio job.
     */
    public function test_a_directly_dispatched_job_also_lands_on_the_module_queue(): void
    {
        Queue::fake();

        TrackCustomerEventJob::dispatch(EventName::PedidoCriado);

        Queue::assertPushedOn('customer-intelligence', TrackCustomerEventJob::class);
    }

    /**
     * A fila do modulo precisa estar entre as que o worker do Docker escuta,
     * senao os eventos ficam parados para sempre. Guarda a fiacao entre a
     * configuracao da aplicacao e a infraestrutura.
     */
    public function test_the_docker_worker_listens_to_the_module_queue(): void
    {
        $compose = file_get_contents(base_path('compose.yaml'));
        $fila = config('customer-intelligence-internal.queue.name');

        $this->assertMatchesRegularExpression(
            '/--queue=[^\s]*'.preg_quote($fila, '/').'/',
            $compose,
            "O worker do compose.yaml precisa incluir a fila {$fila} em --queue."
        );
    }

    public function test_track_does_not_write_to_the_database_by_itself(): void
    {
        Bus::fake();

        $this->service()->track(EventName::PedidoEnviado);

        // A gravacao e responsabilidade do worker, nao da requisicao.
        $this->assertDatabaseCount('ci_events', 0);
    }

    // ─── O que viaja com o job ────────────────────────────────────────────

    public function test_the_job_carries_the_session_resolved_for_the_request(): void
    {
        Bus::fake();

        $session = app(ResolveVisitorSession::class)('visitante-fila', 'sessao-fila');
        app(VisitorContext::class)->setSession($session);

        $this->service()->track(EventName::CarrinhoCheckoutIniciado);

        Bus::assertDispatched(
            TrackCustomerEventJob::class,
            fn (TrackCustomerEventJob $job) => $job->session?->id === $session->id
        );
    }

    /**
     * O usuario e apurado no despacho porque o job pode rodar depois do logout,
     * quando `Auth::id()` ja nao diria nada.
     */
    public function test_the_job_carries_the_user_captured_at_dispatch_time(): void
    {
        Bus::fake();

        $user = User::factory()->create();
        $this->actingAs($user);

        $this->service()->track(EventName::PedidoCriado);

        Bus::assertDispatched(
            TrackCustomerEventJob::class,
            fn (TrackCustomerEventJob $job) => $job->userId === $user->id
        );
    }

    public function test_the_captured_user_survives_a_logout_before_processing(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $job = new TrackCustomerEventJob(EventName::PedidoCriado, userId: $user->id);

        auth()->logout();
        $job->handle($this->service());

        $this->assertSame($user->id, TrackedEvent::sole()->user_id);
    }

    public function test_the_job_carries_the_domain_entity(): void
    {
        Bus::fake();

        $product = $this->makeProduct();
        $this->service()->track(EventName::ProdutoVisualizado, entity: $product);

        Bus::assertDispatched(
            TrackCustomerEventJob::class,
            fn (TrackCustomerEventJob $job) => $job->entity?->is($product) === true
        );
    }

    // ─── Ponta a ponta ────────────────────────────────────────────────────

    public function test_the_event_reaches_the_database_when_the_queue_runs(): void
    {
        $product = $this->makeProduct();
        $session = app(ResolveVisitorSession::class)('visitante-e2e', 'sessao-e2e');
        app(VisitorContext::class)->setSession($session);

        // Sem fake: em testes QUEUE_CONNECTION=sync, entao o job e processado
        // na hora e o caminho inteiro e exercitado.
        $this->service()->track(EventName::ProdutoAdicionadoCarrinho, ['quantidade' => 3], $product);

        $event = TrackedEvent::sole();
        $this->assertSame(EventName::ProdutoAdicionadoCarrinho, $event->event_name);
        $this->assertSame('produto', $event->event_category);
        $this->assertSame(['quantidade' => 3], $event->properties);
        $this->assertSame($session->id, $event->session_id);
        $this->assertSame($session->visitor_id, $event->visitor_id);
        $this->assertSame($product->id, $event->entity_id);
    }

    public function test_sensitive_properties_are_still_redacted_through_the_queue(): void
    {
        $this->service()->track(EventName::PedidoCriado, ['cpf' => '000.000.000-00', 'total' => 12.5]);

        $stored = TrackedEvent::sole()->properties;
        $this->assertSame('[redigido]', $stored['cpf']);
        $this->assertSame(12.5, $stored['total']);
    }

    public function test_occurred_at_is_frozen_at_dispatch_not_at_processing(): void
    {
        Carbon::setTestNow('2026-08-25 08:00:00');
        $job = new TrackCustomerEventJob(EventName::PedidoEnviado);

        Carbon::setTestNow('2026-08-25 08:40:00');
        $job->handle($this->service());

        $event = TrackedEvent::sole();
        $this->assertSame('2026-08-25 08:00:00', $event->occurred_at->toDateTimeString());
        // created_at registra a gravacao; os dois divergem de proposito.
        $this->assertSame('2026-08-25 08:40:00', $event->created_at->toDateTimeString());

        Carbon::setTestNow();
    }

    // ─── Fiacao com a aplicacao ───────────────────────────────────────────

    /**
     * Os eventos de negocio tambem precisam cair na fila propria — nao adianta
     * a fiacao estar certa se o destino estiver errado.
     */
    public function test_events_from_real_browsing_land_on_the_module_queue(): void
    {
        Queue::fake();

        $product = $this->makeProduct();
        $this->get(route('loja.produto', [$product->expositor->slug, $product->slug]))->assertOk();
        app(CartService::class)->add($product->ofertaVigente, 1);

        Queue::assertPushedOn('customer-intelligence', TrackCustomerEventJob::class);
        Queue::assertPushed(TrackCustomerEventJob::class, 2);
    }
}
