<?php

namespace Tests\Feature\CustomerIntelligence;

use App\CustomerIntelligence\Enums\ConsentState;
use App\CustomerIntelligence\Enums\EventName;
use App\CustomerIntelligence\Facades\CustomerIntelligence;
use App\CustomerIntelligence\Models\DailyMetric;
use App\CustomerIntelligence\Models\TrackedEvent;
use App\CustomerIntelligence\Models\Visitor;
use App\CustomerIntelligence\Models\VisitorSession;
use App\CustomerIntelligence\Support\ConsentContext;
use App\CustomerIntelligence\Support\ConsentCookie;
use App\CustomerIntelligence\Support\TrackingPolicy;
use App\Models\Expositor;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithConsent;
use Tests\TestCase;

/**
 * A regra central da GOV-01: analytics e opt-in.
 *
 * Enquanto ninguem respondeu, nada e coletado — nem cookie, nem visitante, nem
 * sessao, nem evento. E o site tem de continuar inteiro do mesmo jeito.
 */
class ConsentPolicyTest extends TestCase
{
    use InteractsWithConsent, RefreshDatabase;

    private function visitorCookie(): string
    {
        return config('customer-intelligence-internal.visitor_cookie.name');
    }

    private function sessionCookie(): string
    {
        return config('customer-intelligence-internal.session_cookie.name');
    }

    private function respostaTemCookie($response, string $nome): bool
    {
        return collect($response->headers->getCookies())
            ->contains(fn ($c) => $c->getName() === $nome && $c->getValue() !== '');
    }

    private function produto(): Product
    {
        $expositor = Expositor::create(['name' => 'Loja Consentimento', 'slug' => 'loja-consentimento']);

        return Product::create([
            'expositor_id' => $expositor->id,
            'item_type' => 'produto',
            'name' => 'Rede de Dormir',
            'slug' => 'rede-de-dormir',
            'price' => 189.90,
            'stock' => 5,
            'is_active' => true,
        ]);
    }

    // ─── Estado inicial: UNKNOWN ──────────────────────────────────────────

    public function test_the_starting_state_is_unknown_and_authorizes_nothing(): void
    {
        $this->assertSame(ConsentState::Unknown, app(ConsentContext::class)->state());
        $this->assertFalse(app(TrackingPolicy::class)->allowsAnalytics());
    }

    public function test_without_an_answer_no_visitor_or_session_is_created(): void
    {
        $this->get('/')->assertOk();

        $this->assertSame(0, Visitor::count(), 'Nenhum visitante antes do aceite.');
        $this->assertSame(0, VisitorSession::count(), 'Nenhuma sessão antes do aceite.');
    }

    public function test_without_an_answer_no_analytics_cookie_is_issued(): void
    {
        $response = $this->get('/');

        $this->assertFalse($this->respostaTemCookie($response, $this->visitorCookie()));
        $this->assertFalse($this->respostaTemCookie($response, $this->sessionCookie()));
    }

    /**
     * O cookie de sessao do Laravel e o XSRF-TOKEN sao essenciais e nao
     * dependem de consentimento: sem eles nao ha login nem formulario.
     */
    public function test_essential_cookies_keep_working_without_an_answer(): void
    {
        $response = $this->get('/');

        $nomes = collect($response->headers->getCookies())->map(fn ($c) => $c->getName())->all();

        $this->assertContains(config('session.cookie'), $nomes);
        $this->assertContains('XSRF-TOKEN', $nomes);
    }

    public function test_without_an_answer_no_event_is_collected(): void
    {
        $produto = $this->produto();

        CustomerIntelligence::track(EventName::ProdutoVisualizado, [], $produto);

        $this->assertSame(0, TrackedEvent::count());
        $this->assertSame(0, DailyMetric::count(), 'Sem evento não há agregado.');
    }

    // ─── REJECTED ─────────────────────────────────────────────────────────

    public function test_rejecting_keeps_collection_off(): void
    {
        $this->rejectingAnalytics();

        $response = $this->get('/');
        $response->assertOk();

        $this->assertSame(0, Visitor::count());
        $this->assertSame(0, VisitorSession::count());
        $this->assertFalse($this->respostaTemCookie($response, $this->visitorCookie()));

        CustomerIntelligence::track(EventName::ProdutoVisualizado);
        $this->assertSame(0, TrackedEvent::count());
    }

    // ─── ACCEPTED ─────────────────────────────────────────────────────────

    public function test_accepting_turns_collection_on(): void
    {
        $this->acceptingAnalytics();

        $response = $this->get('/');
        $response->assertOk();

        $this->assertSame(1, Visitor::count());
        $this->assertSame(1, VisitorSession::count());
        $this->assertTrue($this->respostaTemCookie($response, $this->visitorCookie()));
        $this->assertTrue($this->respostaTemCookie($response, $this->sessionCookie()));
    }

    public function test_accepting_lets_events_through(): void
    {
        $this->acceptingAnalytics();
        $produto = $this->produto();

        CustomerIntelligence::track(EventName::ProdutoVisualizado, [], $produto);

        $this->assertSame(1, TrackedEvent::count());
    }

    /**
     * A decisao vale para os transacionais tambem — foi a decisao de produto da
     * GOV-01, apoiada no fato de que `ci_events` nao alimenta nenhuma
     * funcionalidade operacional.
     */
    public function test_transactional_events_follow_the_same_rule(): void
    {
        $transacionais = [
            EventName::PedidoCriado,
            EventName::PedidoPagamentoConfirmado,
            EventName::PedidoEnviado,
        ];

        foreach ($transacionais as $evento) {
            CustomerIntelligence::track($evento);
        }

        $this->assertSame(0, TrackedEvent::count(), 'Sem aceite, nem os transacionais são coletados.');

        $this->acceptingAnalytics();

        foreach ($transacionais as $evento) {
            CustomerIntelligence::track($evento);
        }

        $this->assertSame(3, TrackedEvent::count());
    }

    // ─── O ponto de decisão é um só ───────────────────────────────────────

    /**
     * A garantia arquitetural pedida na GOV-01: nenhum dos pontos de negocio
     * conhece consentimento. Quem decidir espalhar um `if` por eles faz este
     * teste falhar.
     */
    public function test_the_business_call_sites_never_mention_consent(): void
    {
        $arquivos = [
            app_path('Services/CartService.php'),
            app_path('Services/OrderService.php'),
            app_path('Livewire/Checkout.php'),
            app_path('Livewire/Lojista/Pedidos/PedidoIndex.php'),
            app_path('Listeners/TrackOrderSplitConfirmedEvent.php'),
        ];

        foreach ($arquivos as $arquivo) {
            $conteudo = (string) file_get_contents($arquivo);

            foreach (['ConsentState', 'ConsentContext', 'TrackingPolicy', 'allowsAnalytics'] as $termo) {
                $this->assertStringNotContainsString(
                    $termo,
                    $conteudo,
                    basename($arquivo).' não deve conhecer consentimento — a decisão é do TrackingPolicy.'
                );
            }
        }
    }

    // ─── Nada essencial pode quebrar ──────────────────────────────────────

    public function test_the_cart_still_works_with_analytics_refused(): void
    {
        $this->rejectingAnalytics();

        $user = User::factory()->create();
        $this->actingAs($user);

        $produto = $this->produto();
        $carrinho = app(CartService::class);

        $carrinho->add($produto, 2);

        $this->assertSame(2, $carrinho->items()->sum('quantity'), 'O carrinho não depende de analytics.');
        $this->assertSame(0, TrackedEvent::count());
    }

    public function test_login_still_works_with_analytics_refused(): void
    {
        $this->rejectingAnalytics();

        $user = User::factory()->create();

        $this->actingAs($user)->get('/')->assertOk();

        $this->assertAuthenticatedAs($user);
        $this->assertSame(0, Visitor::count());
    }

    public function test_public_pages_still_render_with_analytics_refused(): void
    {
        $this->rejectingAnalytics();

        foreach (['/', '/produtos', '/agenda', '/politica-de-privacidade'] as $rota) {
            $this->get($rota)->assertOk();
        }

        $this->assertSame(0, Visitor::count());
    }

    // ─── Cookie de preferência ────────────────────────────────────────────

    public function test_a_tampered_preference_cookie_degrades_to_unknown(): void
    {
        $suspeitos = ['', 'lixo', '{quebrado', '{"outro":"campo"}', '{"state":"tudo-liberado"}'];

        foreach ($suspeitos as $bruto) {
            [$estado, $quando] = ConsentCookie::decode($bruto);

            $this->assertSame(ConsentState::Unknown, $estado, "O valor [{$bruto}] deveria cair em Unknown.");
            $this->assertNull($quando);
        }
    }

    public function test_the_preference_cookie_round_trips(): void
    {
        $agora = now();

        [$estado, $quando] = ConsentCookie::decode(
            ConsentCookie::encode(ConsentState::Accepted, $agora)
        );

        $this->assertSame(ConsentState::Accepted, $estado);
        $this->assertSame($agora->toIso8601String(), $quando?->toIso8601String());
    }

    public function test_the_preference_cookie_has_a_neutral_name_and_lasts_twelve_months(): void
    {
        $this->assertSame('fel_privacy_consent', ConsentCookie::name());
        $this->assertStringNotContainsString('jmf', ConsentCookie::name());
        $this->assertSame(60 * 24 * 365, ConsentCookie::minutes());
    }

    // ─── Chave de operação x consentimento ────────────────────────────────

    public function test_the_module_switch_and_consent_are_independent(): void
    {
        config()->set('customer-intelligence-internal.enabled', false);

        $this->acceptingAnalytics();

        $this->assertFalse(
            app(TrackingPolicy::class)->allowsAnalytics(),
            'Módulo desligado vence o aceite: não há coleta para consentir.'
        );
        $this->assertFalse(
            app(TrackingPolicy::class)->needsDecision(),
            'E também não há o que perguntar.'
        );
    }
}
