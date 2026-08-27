<?php

namespace Tests\Feature\CustomerIntelligence;

use App\CustomerIntelligence\Actions\ResolveVisitorSession;
use App\CustomerIntelligence\Enums\EventName;
use App\CustomerIntelligence\Enums\MetricName;
use App\CustomerIntelligence\Jobs\TrackCustomerEventJob;
use App\CustomerIntelligence\Models\DailyMetric;
use App\CustomerIntelligence\Models\TrackedEvent;
use App\CustomerIntelligence\Models\VisitorSession;
use App\CustomerIntelligence\Services\CustomerIntelligenceService;
use App\Models\Expositor;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;
use Tests\Concerns\InteractsWithConsent;
use Tests\TestCase;

/**
 * Idempotencia da gravacao de eventos (CI-09A).
 *
 * O risco: o job tem tres tentativas. Se a primeira gravar o evento e falhar
 * depois, a retentativa nao pode criar um segundo evento nem somar a metrica
 * de novo.
 */
class InternalReliabilityTest extends TestCase
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

    private function openSession(string $uuid = 'v-conf'): VisitorSession
    {
        return app(ResolveVisitorSession::class)($uuid, 'sessao-'.$uuid);
    }

    private function makeProduct(): Product
    {
        $expositor = Expositor::create(['name' => 'Loja Conf', 'slug' => 'loja-conf']);

        return Product::factory()->create([
            'expositor_id' => $expositor->id,
            'item_type' => 'produto',
            'name' => 'Rede Confiável',
            'slug' => 'rede-confiavel',
            'price' => 120.00,
            'is_active' => true,
        ]);
    }

    private function eventCount(): float
    {
        return (float) DailyMetric::where('metric_name', MetricName::Eventos->value)
            ->where('dimension_type', '')
            ->sum('metric_value');
    }

    // ─── event_uuid nasce no despacho ─────────────────────────────────────

    public function test_the_event_uuid_is_created_at_dispatch_not_at_insert(): void
    {
        Bus::fake();

        $this->service()->track(EventName::ProdutoVisualizado);

        Bus::assertDispatched(
            TrackCustomerEventJob::class,
            fn (TrackCustomerEventJob $job) => is_string($job->eventUuid) && $job->eventUuid !== ''
        );
    }

    public function test_the_stored_event_keeps_the_uuid_that_came_from_the_dispatch(): void
    {
        $uuid = (string) Str::orderedUuid();

        (new TrackCustomerEventJob(EventName::PedidoCriado, eventUuid: $uuid))->handle($this->service());

        $this->assertSame($uuid, TrackedEvent::sole()->event_uuid);
    }

    // ─── retentativa ──────────────────────────────────────────────────────

    public function test_running_the_same_job_twice_records_a_single_event(): void
    {
        $job = new TrackCustomerEventJob(
            EventName::ProdutoVisualizado,
            ['produto_id' => 3],
            eventUuid: (string) Str::orderedUuid(),
        );

        $job->handle($this->service());
        $job->handle($this->service());

        $this->assertSame(1, TrackedEvent::count(), 'A retentativa não pode duplicar o evento.');
    }

    public function test_a_retry_does_not_increment_the_metric_again(): void
    {
        $job = new TrackCustomerEventJob(EventName::PedidoCriado, eventUuid: (string) Str::orderedUuid());

        $job->handle($this->service());
        $primeiro = $this->eventCount();

        $job->handle($this->service());

        $this->assertSame(1.0, $primeiro);
        $this->assertSame(1.0, $this->eventCount(), 'A métrica não pode somar duas vezes o mesmo evento.');
        $this->assertSame(
            '1.0000',
            DailyMetric::where('metric_name', MetricName::Conversoes->value)->value('metric_value')
        );
    }

    public function test_a_retry_returns_the_event_that_was_already_recorded(): void
    {
        $uuid = (string) Str::orderedUuid();
        $job = new TrackCustomerEventJob(EventName::PedidoEnviado, eventUuid: $uuid);

        $primeiro = $this->service()->record(EventName::PedidoEnviado, eventUuid: $uuid);
        $job->handle($this->service());

        $this->assertSame(1, TrackedEvent::count());
        $this->assertSame($primeiro->id, TrackedEvent::sole()->id);
    }

    /**
     * Duas gravacoes do mesmo evento logico, como aconteceria se dois workers
     * pegassem a mesma mensagem. A protecao e a chave unica do banco, nao um
     * `if (! exists())` em PHP, que sofreria corrida.
     */
    public function test_concurrent_writes_of_the_same_logical_event_collapse_into_one(): void
    {
        $uuid = (string) Str::orderedUuid();

        $a = $this->service()->record(EventName::ProdutoAdicionadoCarrinho, eventUuid: $uuid);
        $b = $this->service()->record(EventName::ProdutoAdicionadoCarrinho, eventUuid: $uuid);

        $this->assertSame($a->id, $b->id);
        $this->assertSame(1, TrackedEvent::count());
        $this->assertSame(1.0, $this->eventCount());
    }

    public function test_idempotency_holds_for_events_with_and_without_an_entity(): void
    {
        $product = $this->makeProduct();

        $comEntidade = new TrackCustomerEventJob(
            EventName::ProdutoVisualizado,
            entity: $product,
            eventUuid: (string) Str::orderedUuid(),
        );
        $semEntidade = new TrackCustomerEventJob(
            EventName::CarrinhoCheckoutIniciado,
            eventUuid: (string) Str::orderedUuid(),
        );

        foreach ([$comEntidade, $semEntidade] as $job) {
            $job->handle($this->service());
            $job->handle($this->service());
        }

        $this->assertSame(2, TrackedEvent::count());
        $this->assertSame(2.0, $this->eventCount());
        $this->assertSame($product->id, TrackedEvent::whereNotNull('entity_id')->sole()->entity_id);
    }

    public function test_idempotency_preserves_the_captured_user(): void
    {
        $user = User::factory()->create();
        $job = new TrackCustomerEventJob(
            EventName::PedidoCriado,
            userId: $user->id,
            eventUuid: (string) Str::orderedUuid(),
        );

        $job->handle($this->service());
        $job->handle($this->service());

        $this->assertSame(1, TrackedEvent::count());
        $this->assertSame($user->id, TrackedEvent::sole()->user_id);
    }

    public function test_the_visitor_and_session_survive_a_retry(): void
    {
        $session = $this->openSession();
        $job = new TrackCustomerEventJob(
            EventName::ProdutoVisualizado,
            session: $session,
            eventUuid: (string) Str::orderedUuid(),
        );

        $job->handle($this->service());
        $job->handle($this->service());

        $evento = TrackedEvent::sole();
        $this->assertSame($session->id, $evento->session_id);
        $this->assertSame($session->visitor_id, $evento->visitor_id);
    }

    // ─── falhas reais continuam falhando ──────────────────────────────────

    /**
     * Idempotencia nao pode virar desculpa para engolir erro. So a colisao da
     * chave unica de `ci_events` — e apenas quando existe um evento com aquele
     * `event_uuid` — e tratada como retentativa. Qualquer outra falha de banco
     * continua subindo, para o job falhar e ir para retry.
     */
    public function test_a_real_database_error_is_not_swallowed(): void
    {
        $this->expectException(QueryException::class);

        // Usuario inexistente: viola a foreign key de ci_events.user_id.
        $this->service()->record(
            EventName::PedidoCriado,
            userId: 999999,
            eventUuid: (string) Str::orderedUuid(),
        );
    }

    public function test_a_failed_write_leaves_no_metric_behind(): void
    {
        try {
            $this->service()->record(
                EventName::PedidoCriado,
                userId: 999999,
                eventUuid: (string) Str::orderedUuid(),
            );
        } catch (QueryException) {
            // esperado
        }

        $this->assertSame(0, TrackedEvent::count());
        $this->assertSame(0.0, $this->eventCount(), 'Sem evento gravado, nenhuma metrica pode ter sido somada.');
    }
}
