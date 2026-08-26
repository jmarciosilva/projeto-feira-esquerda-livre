<?php

namespace Tests\Feature\CustomerIntelligence;

use App\CustomerIntelligence\Actions\ResolveVisitorSession;
use App\CustomerIntelligence\Enums\EventName;
use App\CustomerIntelligence\Http\Middleware\TrackVisitorSession;
use App\CustomerIntelligence\Models\Visitor;
use App\CustomerIntelligence\Models\VisitorSession;
use App\CustomerIntelligence\Services\CustomerIntelligenceService;
use App\CustomerIntelligence\Support\VisitorContext;
use App\Models\User;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Cookie\CookieValuePrefix;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\InteractsWithConsent;
use Tests\TestCase;

/**
 * Coleta de visitante e sessao pelo modulo interno (CI-03).
 */
class InternalVisitorTrackingTest extends TestCase
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

    private function visitorCookie(): string
    {
        return config('customer-intelligence-internal.visitor_cookie.name');
    }

    private function sessionCookie(): string
    {
        return config('customer-intelligence-internal.session_cookie.name');
    }

    private function action(): ResolveVisitorSession
    {
        return app(ResolveVisitorSession::class);
    }

    /**
     * Valor legível de um cookie da resposta. O EncryptCookies criptografa a
     * saída e ainda prefixa o valor com um HMAC do nome do cookie.
     */
    private function plainCookie(TestResponse $response, string $name): ?string
    {
        $cookie = collect($response->headers->getCookies())
            ->first(fn ($c) => $c->getName() === $name);

        return $cookie === null
            ? null
            : CookieValuePrefix::remove(decrypt($cookie->getValue(), false));
    }

    /**
     * Repassa os cookies da resposta anterior, como um navegador faria. O
     * cliente de teste do Laravel não faz isso sozinho.
     *
     * @return $this
     */
    private function carryingCookiesFrom(TestResponse $response): self
    {
        return $this->withCookies(array_filter([
            $this->visitorCookie() => $this->plainCookie($response, $this->visitorCookie()),
            $this->sessionCookie() => $this->plainCookie($response, $this->sessionCookie()),
        ]));
    }

    // ─── Registro do middleware ───────────────────────────────────────────

    public function test_the_middleware_is_registered_in_the_web_group(): void
    {
        $groups = app(Kernel::class)->getMiddlewareGroups();

        $this->assertContains(TrackVisitorSession::class, $groups['web']);
    }

    /**
     * Desde a CI-07 o ServiceProvider do SDK nao e mais descoberto, entao o
     * middleware dele nao entra no grupo `web`. A coleta passou a ser
     * responsabilidade exclusiva do modulo interno.
     *
     * O nome da classe e comparado como string de proposito: importa-la
     * reintroduziria uma dependencia de runtime no proprio teste.
     */
    public function test_the_sdk_middleware_no_longer_runs(): void
    {
        $web = app(Kernel::class)->getMiddlewareGroups()['web'];

        $doSdk = array_filter($web, fn (string $m) => str_starts_with($m, 'JmfSystem\\'));

        $this->assertSame([], array_values($doSdk), 'Nenhum middleware do SDK deve participar do grupo web.');
        $this->assertContains(TrackVisitorSession::class, $web);
    }

    // ─── Primeira visita ──────────────────────────────────────────────────

    public function test_a_first_visit_creates_one_visitor_and_one_session(): void
    {
        $this->get('/')->assertOk();

        $this->assertSame(1, Visitor::count());
        $this->assertSame(1, VisitorSession::count());

        $visitor = Visitor::sole();
        $this->assertNotNull($visitor->first_seen_at);
        $this->assertNotNull($visitor->last_seen_at);
        $this->assertNull($visitor->user_id);

        $session = VisitorSession::sole();
        $this->assertSame($visitor->id, $session->visitor_id);
        $this->assertTrue($session->isOpen());
        $this->assertSame('/', $session->landing_url);
    }

    /**
     * O SDK externo e o módulo emitem os mesmos cookies. O CookieJar indexa a
     * fila por nome, então só um Set-Cookie de cada deve sair.
     */
    public function test_only_one_cookie_of_each_name_is_sent(): void
    {
        $response = $this->get('/');

        foreach ([$this->visitorCookie(), $this->sessionCookie()] as $name) {
            $encontrados = array_filter(
                $response->headers->getCookies(),
                fn ($cookie) => $cookie->getName() === $name
            );

            $this->assertCount(1, $encontrados, "Esperava um único cookie {$name}.");
        }
    }

    /**
     * O ponto central da convivência: o identificador gravado no banco local
     * tem de ser o mesmo que vai no cookie — senão o servidor remoto e o banco
     * conheceriam o mesmo visitante por dois nomes.
     */
    public function test_the_stored_visitor_matches_the_cookie_that_is_sent(): void
    {
        $response = $this->get('/');

        $noCookie = $this->plainCookie($response, $this->visitorCookie());

        $this->assertNotNull($noCookie);
        $this->assertSame(Visitor::sole()->visitor_uuid, $noCookie);

        // O mesmo vale para a sessão.
        $this->assertSame(
            VisitorSession::sole()->session_uuid,
            $this->plainCookie($response, $this->sessionCookie())
        );
    }

    // ─── Visitas seguintes ────────────────────────────────────────────────

    public function test_a_returning_visitor_is_not_duplicated(): void
    {
        $visitor = Visitor::create(['visitor_uuid' => 'visitante-conhecido', 'first_seen_at' => now()]);
        $session = VisitorSession::create([
            'session_uuid' => 'sessao-conhecida',
            'visitor_id' => $visitor->id,
            'started_at' => now(),
            'last_activity_at' => now(),
        ]);

        $this->withCookies([
            $this->visitorCookie() => 'visitante-conhecido',
            $this->sessionCookie() => 'sessao-conhecida',
        ])->get('/')->assertOk();

        $this->assertSame(1, Visitor::count());
        $this->assertSame(1, VisitorSession::count());
        $this->assertSame($session->id, VisitorSession::sole()->id);
    }

    public function test_an_expired_session_starts_a_new_one_keeping_the_same_visitor(): void
    {
        $visitor = Visitor::create(['visitor_uuid' => 'visitante-antigo', 'first_seen_at' => now()]);
        $antiga = VisitorSession::create([
            'session_uuid' => 'sessao-antiga',
            'visitor_id' => $visitor->id,
            'started_at' => now()->subHours(3),
            'last_activity_at' => now()->subHours(3),
        ]);

        $this->withCookies([
            $this->visitorCookie() => 'visitante-antigo',
            $this->sessionCookie() => 'sessao-antiga',
        ])->get('/')->assertOk();

        $this->assertSame(1, Visitor::count(), 'O visitante deve ser preservado.');
        $this->assertSame(2, VisitorSession::count(), 'Uma nova sessão deve ser aberta.');
        $this->assertNotNull($antiga->fresh()->ended_at, 'A sessão antiga deve ser encerrada.');
    }

    // ─── Identificação ────────────────────────────────────────────────────

    public function test_an_authenticated_visit_links_the_visitor_to_the_user(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/')->assertOk();

        $this->assertSame($user->id, Visitor::sole()->user_id);
        $this->assertTrue(Visitor::sole()->isIdentified());
    }

    public function test_an_existing_link_is_not_overwritten_by_another_user(): void
    {
        $primeiro = User::factory()->create();
        $segundo = User::factory()->create();

        $visitor = $this->action()('visitante-compartilhado', 'sessao-a', $primeiro->id)->visitor;
        $this->action()('visitante-compartilhado', 'sessao-a', $segundo->id);

        $this->assertSame($primeiro->id, $visitor->fresh()->user_id);
    }

    // ─── Origem da sessão ─────────────────────────────────────────────────

    public function test_utm_parameters_are_captured_when_the_session_opens(): void
    {
        $this->get('/?utm_source=instagram&utm_medium=social&utm_campaign=feira-agosto')->assertOk();

        $session = VisitorSession::sole();
        $this->assertSame('instagram', $session->utm_source);
        $this->assertSame('social', $session->utm_medium);
        $this->assertSame('feira-agosto', $session->utm_campaign);
    }

    /**
     * Minimização: nem a landing nem o referrer guardam query string, que pode
     * carregar dado pessoal.
     */
    public function test_landing_and_referrer_never_keep_the_query_string(): void
    {
        $this->withHeader('referer', 'https://google.com/search?q=maria+silva+cpf')
            ->get('/produtos?busca=segredo')
            ->assertOk();

        $session = VisitorSession::sole();
        $this->assertSame('/produtos', $session->landing_url);
        $this->assertSame('https://google.com/search', $session->referrer);
        $this->assertStringNotContainsString('segredo', (string) $session->landing_url);
        $this->assertStringNotContainsString('maria', (string) $session->referrer);
    }

    // ─── Contexto por requisição ──────────────────────────────────────────

    public function test_the_context_is_empty_outside_the_http_cycle(): void
    {
        $this->assertFalse(app(VisitorContext::class)->isResolved());
        $this->assertNull(app(VisitorContext::class)->session());
        $this->assertNull(app(VisitorContext::class)->visitor());
    }

    public function test_the_service_uses_the_resolved_context_when_nothing_is_passed(): void
    {
        $session = $this->action()('visitante-ctx', 'sessao-ctx');
        app(VisitorContext::class)->setSession($session);

        $event = app(CustomerIntelligenceService::class)->record(EventName::ProdutoVisualizado);

        $this->assertSame($session->id, $event->session_id);
        $this->assertSame($session->visitor_id, $event->visitor_id);
    }

    public function test_an_explicit_session_still_wins_over_the_context(): void
    {
        $doContexto = $this->action()('visitante-ctx', 'sessao-ctx');
        app(VisitorContext::class)->setSession($doContexto);

        $explicita = $this->action()('outro-visitante', 'outra-sessao');
        $event = app(CustomerIntelligenceService::class)->record(
            EventName::PedidoCriado,
            session: $explicita
        );

        $this->assertSame($explicita->id, $event->session_id);
    }

    // ─── Não altera o comportamento atual ─────────────────────────────────

    public function test_browsing_still_does_not_record_any_event(): void
    {
        $primeira = $this->get('/');
        $primeira->assertOk();

        $this->carryingCookiesFrom($primeira)->get('/produtos')->assertOk();

        // A coleta de visitante e sessão está ligada; a de eventos não. As 7
        // chamadas continuam saindo pelo SDK externo até a CI-05.
        $this->assertDatabaseCount('ci_events', 0);

        // E o mesmo navegador continua sendo um único visitante entre páginas.
        $this->assertSame(1, Visitor::count());
        $this->assertSame(1, VisitorSession::count());
    }

    // ─── Regra de sessão isolada do HTTP ──────────────────────────────────

    public function test_activity_within_the_window_keeps_the_same_session(): void
    {
        Carbon::setTestNow('2026-08-25 10:00:00');
        $primeira = $this->action()('v-1', 's-1');

        Carbon::setTestNow('2026-08-25 10:20:00');
        $segunda = $this->action()('v-1', $primeira->session_uuid);

        $this->assertSame($primeira->id, $segunda->id);
        $this->assertSame('2026-08-25 10:20:00', $segunda->last_activity_at->toDateTimeString());

        Carbon::setTestNow();
    }

    public function test_a_session_uuid_from_another_visitor_starts_a_fresh_session(): void
    {
        $alheia = $this->action()('visitante-a', 'sessao-a');

        $intrusa = $this->action()('visitante-b', $alheia->session_uuid);

        $this->assertNotSame($alheia->id, $intrusa->id);
        $this->assertSame(2, Visitor::count());
        $this->assertSame(2, VisitorSession::count());
    }
}
