<?php

namespace Tests\Feature\CustomerIntelligence;

use App\CustomerIntelligence\Enums\EventName;
use App\CustomerIntelligence\Jobs\TrackCustomerEventJob;
use App\CustomerIntelligence\Models\TrackedEvent;
use App\CustomerIntelligence\Models\Visitor;
use App\CustomerIntelligence\Models\VisitorSession;
use App\CustomerIntelligence\Services\CustomerIntelligenceService;
use App\CustomerIntelligence\Support\PropertySanitizer;
use App\Models\Expositor;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Gravacao de eventos pelo modulo interno (CI-02).
 *
 * Importante: nenhuma chamada da aplicacao usa este caminho ainda. Os sete
 * eventos atuais continuam saindo pelo SDK externo. Estes testes exercitam a
 * fundacao isoladamente.
 */
class InternalEventRecordingTest extends TestCase
{
    use RefreshDatabase;

    private function service(): CustomerIntelligenceService
    {
        return app(CustomerIntelligenceService::class);
    }

    private function makeVisitorWithSession(?User $user = null): VisitorSession
    {
        $visitor = Visitor::create(['user_id' => $user?->id, 'first_seen_at' => now()]);

        return VisitorSession::create(['visitor_id' => $visitor->id, 'started_at' => now()]);
    }

    private function makeProduct(): Product
    {
        $expositor = Expositor::create(['name' => 'Loja Recording', 'slug' => 'loja-recording']);

        return Product::create([
            'expositor_id' => $expositor->id,
            'item_type' => 'produto',
            'name' => 'Rede de Descanso',
            'slug' => 'rede-de-descanso',
            'price' => 199.90,
            'is_active' => true,
        ]);
    }

    // ─── Service ──────────────────────────────────────────────────────────

    public function test_it_records_an_event_with_category_derived_from_the_name(): void
    {
        $event = $this->service()->record(EventName::PedidoCriado, ['valor_total' => 89.9]);

        $this->assertDatabaseCount('ci_events', 1);
        $this->assertSame(EventName::PedidoCriado, $event->event_name);
        $this->assertSame('pedido', $event->event_category);
        $this->assertSame(['valor_total' => 89.9], $event->properties);
        $this->assertNotNull($event->event_uuid);
        $this->assertNotNull($event->occurred_at);
    }

    public function test_it_links_visitor_session_and_user(): void
    {
        $user = User::factory()->create();
        $session = $this->makeVisitorWithSession($user);

        $event = $this->service()->record(EventName::ProdutoVisualizado, session: $session);

        $this->assertSame($session->id, $event->session_id);
        $this->assertSame($session->visitor_id, $event->visitor_id);
        // O vinculo registrado no visitante vale mesmo sem requisicao autenticada.
        $this->assertSame($user->id, $event->user_id);
    }

    public function test_it_falls_back_to_the_authenticated_user_when_the_visitor_is_anonymous(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $event = $this->service()->record(EventName::CarrinhoCheckoutIniciado);

        $this->assertSame($user->id, $event->user_id);
        $this->assertNull($event->visitor_id);
    }

    public function test_it_records_an_event_with_no_visitor_no_session_and_no_user(): void
    {
        $event = $this->service()->record(EventName::PedidoEnviado);

        $this->assertNull($event->visitor_id);
        $this->assertNull($event->session_id);
        $this->assertNull($event->user_id);
        $this->assertDatabaseCount('ci_events', 1);
    }

    public function test_it_attaches_the_domain_entity_instead_of_burying_the_id_in_properties(): void
    {
        $product = $this->makeProduct();

        $event = $this->service()->record(EventName::ProdutoVisualizado, entity: $product);

        $this->assertSame($product->getMorphClass(), $event->entity_type);
        $this->assertSame($product->id, $event->entity_id);
        $this->assertTrue($event->fresh()->entity->is($product));
    }

    public function test_empty_properties_are_stored_as_null(): void
    {
        $event = $this->service()->record(EventName::PedidoEnviado);

        $this->assertNull($event->fresh()->properties);
    }

    public function test_it_honours_an_explicit_occurred_at(): void
    {
        $moment = Carbon::parse('2026-07-04 08:15:00');

        $event = $this->service()->record(EventName::PedidoCriado, occurredAt: $moment);

        $this->assertSame('2026-07-04 08:15:00', $event->fresh()->occurred_at->toDateTimeString());
    }

    public function test_it_never_calls_the_remote_platform(): void
    {
        Http::preventStrayRequests();

        $this->service()->record(EventName::ProdutoAdicionadoCarrinho, ['produto_id' => 1]);

        $this->assertDatabaseCount('ci_events', 1);
    }

    // ─── Minimizacao de dados ─────────────────────────────────────────────

    public function test_sensitive_properties_are_redacted_before_being_stored(): void
    {
        $event = $this->service()->record(EventName::PedidoCriado, [
            'valor_total' => 50.75,
            'cpf' => '123.456.789-00',
            'cliente' => [
                'password' => 'segredo',
                'primeiro_nome' => 'Maria',
            ],
        ]);

        $stored = $event->fresh()->properties;

        $this->assertSame(PropertySanitizer::REDACTED, $stored['cpf']);
        $this->assertSame(PropertySanitizer::REDACTED, $stored['cliente']['password']);
        // O que nao e sensivel passa intacto.
        $this->assertSame(50.75, $stored['valor_total']);
        $this->assertSame('Maria', $stored['cliente']['primeiro_nome']);
    }

    public function test_sanitizer_recognises_sensitive_keys_regardless_of_case(): void
    {
        $sanitizer = new PropertySanitizer;

        foreach (['CPF', 'Card_Number', 'api_key', 'user_token', 'senha'] as $key) {
            $this->assertTrue($sanitizer->isSensitive($key), "{$key} deveria ser sensivel.");
        }

        foreach (['produto_id', 'valor_total', 'quantidade', 'transportadora'] as $key) {
            $this->assertFalse($sanitizer->isSensitive($key), "{$key} nao deveria ser sensivel.");
        }
    }

    // ─── Job ──────────────────────────────────────────────────────────────

    public function test_the_job_records_the_event_when_handled(): void
    {
        $session = $this->makeVisitorWithSession();
        $product = $this->makeProduct();

        $job = new TrackCustomerEventJob(
            event: EventName::ProdutoAdicionadoCarrinho,
            properties: ['quantidade' => 2],
            entity: $product,
            session: $session,
        );

        $job->handle($this->service());

        $event = TrackedEvent::sole();
        $this->assertSame(EventName::ProdutoAdicionadoCarrinho, $event->event_name);
        $this->assertSame(['quantidade' => 2], $event->properties);
        $this->assertSame($session->id, $event->session_id);
        $this->assertSame($product->id, $event->entity_id);
    }

    public function test_the_job_freezes_occurred_at_at_dispatch_time(): void
    {
        Carbon::setTestNow('2026-08-25 09:00:00');
        $job = new TrackCustomerEventJob(EventName::PedidoCriado);

        // Tempo passa entre o despacho e o processamento pela fila.
        Carbon::setTestNow('2026-08-25 09:45:00');
        $job->handle($this->service());

        $this->assertSame('2026-08-25 09:00:00', TrackedEvent::sole()->occurred_at->toDateTimeString());

        Carbon::setTestNow();
    }

    public function test_nothing_in_the_application_dispatches_the_job_yet(): void
    {
        Bus::fake();

        // Percursos que na CI-06 passarao a alimentar o modulo interno. Hoje
        // eles continuam indo apenas para o SDK externo — sem escrita dupla.
        $product = $this->makeProduct();
        $this->get(route('loja.produto', [$product->expositor->slug, $product->slug]))->assertOk();
        app(CartService::class)->add($product, 1);

        Bus::assertNotDispatched(TrackCustomerEventJob::class);
        $this->assertDatabaseCount('ci_events', 0);
    }
}
