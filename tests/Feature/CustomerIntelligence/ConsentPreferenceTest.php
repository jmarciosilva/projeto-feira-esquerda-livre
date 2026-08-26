<?php

namespace Tests\Feature\CustomerIntelligence;

use App\CustomerIntelligence\Enums\ConsentState;
use App\CustomerIntelligence\Enums\EventName;
use App\CustomerIntelligence\Facades\CustomerIntelligence;
use App\CustomerIntelligence\Models\TrackedEvent;
use App\CustomerIntelligence\Models\Visitor;
use App\CustomerIntelligence\Support\ConsentCookie;
use Illuminate\Cookie\CookieValuePrefix;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\Concerns\InteractsWithConsent;
use Tests\TestCase;

/**
 * Banner, pagina de preferencias e as transicoes entre os tres estados.
 */
class ConsentPreferenceTest extends TestCase
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

    private function cookieDaResposta(TestResponse $response, string $nome): ?string
    {
        $cookie = collect($response->headers->getCookies())
            ->first(fn ($c) => $c->getName() === $nome);

        if ($cookie === null || $cookie->getValue() === '') {
            return null;
        }

        return CookieValuePrefix::remove(decrypt($cookie->getValue(), false));
    }

    /**
     * Um cookie de expiracao: enviado com validade no passado, para que o
     * navegador o descarte.
     *
     * O valor nao entra na verificacao porque o EncryptCookies cifra tambem o
     * conteudo vazio de um cookie de remocao — o que chega ao navegador nao e
     * mais a string vazia, e sim um criptograma dela. Quem manda aqui e a data
     * de expiracao.
     */
    private function foiExpirado(TestResponse $response, string $nome): bool
    {
        $cookie = collect($response->headers->getCookies())
            ->first(fn ($c) => $c->getName() === $nome);

        return $cookie !== null
            && $cookie->getExpiresTime() > 0
            && $cookie->getExpiresTime() < time();
    }

    // ─── Banner ───────────────────────────────────────────────────────────

    public function test_the_banner_appears_while_the_question_is_unanswered(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Podemos medir sua navegação?')
            ->assertSee('Aceitar')
            ->assertSee('Recusar');
    }

    public function test_the_banner_disappears_after_accepting(): void
    {
        $this->acceptingAnalytics()
            ->get('/')
            ->assertOk()
            ->assertDontSee('Podemos medir sua navegação?');
    }

    public function test_the_banner_disappears_after_rejecting(): void
    {
        $this->rejectingAnalytics()
            ->get('/')
            ->assertOk()
            ->assertDontSee('Podemos medir sua navegação?');
    }

    /**
     * Sem dark pattern: as duas opcoes tem a mesma largura, o mesmo peso de
     * fonte e a mesma area de clique. Se alguem reduzir a recusa a um link
     * discreto, esta comparacao denuncia.
     */
    public function test_accept_and_reject_carry_the_same_visual_weight(): void
    {
        $html = (string) file_get_contents(resource_path('views/partials/consent-banner.blade.php'));

        preg_match_all('/<button[^>]*class="([^"]*)"/', $html, $encontrados);

        $this->assertCount(2, $encontrados[1], 'O banner deve ter exatamente dois botões.');
        $this->assertSame(
            $encontrados[1][0],
            $encontrados[1][1],
            'Aceitar e Recusar precisam ter exatamente as mesmas classes de layout.'
        );
    }

    // ─── Página de preferências ───────────────────────────────────────────

    public function test_the_preferences_page_is_public(): void
    {
        $this->get(route('privacidade.preferencias'))
            ->assertOk()
            ->assertSee('Preferências de Privacidade')
            ->assertSee('Essenciais')
            ->assertSee('Sempre ativo');
    }

    public function test_the_preferences_page_shows_the_current_state(): void
    {
        $this->acceptingAnalytics()
            ->get(route('privacidade.preferencias'))
            ->assertOk()
            ->assertSee('Aceito');
    }

    public function test_the_footer_links_to_the_preferences_page(): void
    {
        $this->get('/politica-de-privacidade')
            ->assertOk()
            ->assertSee(route('privacidade.preferencias'));
    }

    // ─── Gravação da decisão ──────────────────────────────────────────────

    public function test_accepting_stores_the_preference_cookie(): void
    {
        $response = $this->post(route('privacidade.consentimento'), ['decision' => 'accepted']);

        $response->assertRedirect();

        [$estado] = ConsentCookie::decode($this->cookieDaResposta($response, ConsentCookie::name()));

        $this->assertSame(ConsentState::Accepted, $estado);
    }

    public function test_rejecting_stores_the_preference_cookie(): void
    {
        $response = $this->post(route('privacidade.consentimento'), ['decision' => 'rejected']);

        [$estado] = ConsentCookie::decode($this->cookieDaResposta($response, ConsentCookie::name()));

        $this->assertSame(ConsentState::Rejected, $estado);
    }

    public function test_an_invalid_decision_is_refused(): void
    {
        $this->post(route('privacidade.consentimento'), ['decision' => 'talvez'])
            ->assertSessionHasErrors('decision');
    }

    /**
     * `unknown` e estado do sistema, nao opcao de formulario: aceita-lo
     * permitiria devolver alguem ao limbo do banner por requisicao forjada.
     */
    public function test_unknown_cannot_be_chosen_through_the_form(): void
    {
        $this->post(route('privacidade.consentimento'), ['decision' => 'unknown'])
            ->assertSessionHasErrors('decision');
    }

    // ─── Transição ACCEPTED → REJECTED ────────────────────────────────────

    public function test_rejecting_after_accepting_expires_the_analytics_cookies(): void
    {
        $this->acceptingAnalytics();
        $this->get('/')->assertOk();

        $this->assertSame(1, Visitor::count(), 'Precondição: havia coleta em curso.');

        $this->forgetResolvedConsent();

        $response = $this->post(route('privacidade.consentimento'), ['decision' => 'rejected']);

        $this->assertTrue(
            $this->foiExpirado($response, $this->visitorCookie()),
            'O cookie de visitante deve ser expirado na hora.'
        );
        $this->assertTrue(
            $this->foiExpirado($response, $this->sessionCookie()),
            'O cookie de sessão também.'
        );
    }

    public function test_rejecting_stops_tracking_within_the_same_request(): void
    {
        $this->acceptingAnalytics();
        $this->forgetResolvedConsent();

        $this->post(route('privacidade.consentimento'), ['decision' => 'rejected']);

        // Mesmo ciclo: o contexto ja tem de enxergar a recusa.
        CustomerIntelligence::track(EventName::ProdutoVisualizado);

        $this->assertSame(0, TrackedEvent::count());
    }

    /**
     * Recusar interrompe a coleta; nao apaga o passado. Desvincular historico e
     * ato explicito, pelo `customer-intelligence:forget-user`.
     */
    public function test_rejecting_never_deletes_the_existing_history(): void
    {
        $this->acceptingAnalytics();
        $this->get('/')->assertOk();
        CustomerIntelligence::track(EventName::ProdutoVisualizado);

        $eventosAntes = TrackedEvent::count();
        $visitantesAntes = Visitor::count();

        $this->assertGreaterThan(0, $eventosAntes);

        $this->forgetResolvedConsent();
        $this->post(route('privacidade.consentimento'), ['decision' => 'rejected']);

        $this->assertSame($eventosAntes, TrackedEvent::count(), 'O histórico permanece.');
        $this->assertSame($visitantesAntes, Visitor::count(), 'O visitante também.');
    }

    // ─── Transição REJECTED → ACCEPTED ────────────────────────────────────

    /**
     * Voltar a aceitar gera identidade nova. Religar ao visitante anterior
     * seria reidentificar justamente quem pediu para nao ser seguido — e os
     * cookies antigos ja foram expirados na recusa, entao a identidade nova e
     * consequencia natural, nao um caso especial.
     */
    public function test_accepting_again_creates_a_brand_new_identity(): void
    {
        $this->acceptingAnalytics();
        $this->get('/')->assertOk();

        $primeiro = Visitor::sole()->visitor_uuid;

        // A recusa expira os cookies de analytics: e este passo que faz o
        // navegador chegar ao proximo aceite sem identidade nenhuma.
        $this->forgetResolvedConsent();
        $recusa = $this->post(route('privacidade.consentimento'), ['decision' => 'rejected']);

        $this->assertTrue(
            $this->foiExpirado($recusa, $this->visitorCookie()),
            'Precondição da identidade nova: o cookie antigo precisa ter sido expirado.'
        );

        // Novo aceite, ja sem os cookies antigos.
        $this->forgetResolvedConsent();
        $this->acceptingAnalytics();
        $this->get('/')->assertOk();

        $this->assertSame(2, Visitor::count(), 'Uma identidade nova, e não a antiga reaproveitada.');
        $this->assertNotSame($primeiro, Visitor::latest('id')->first()->visitor_uuid);
    }
}
