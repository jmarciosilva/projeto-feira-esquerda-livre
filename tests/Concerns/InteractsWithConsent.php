<?php

namespace Tests\Concerns;

use App\CustomerIntelligence\Enums\ConsentState;
use App\CustomerIntelligence\Support\ConsentContext;
use App\CustomerIntelligence\Support\ConsentCookie;
use Illuminate\Support\Carbon;

/**
 * Consentimento de analytics nos testes.
 *
 * Desde a GOV-01 o modelo e opt-in: sem aceite nao ha visitante, sessao, cookie
 * nem evento. Todo teste que exercita a COLETA precisa declarar o aceite; e
 * justamente por precisar declarar que o padrao continua sendo verificavel —
 * um teste que esquecer de chamar isto vera a coleta desligada, que e o
 * comportamento correto para quem nao respondeu.
 *
 * Duas coisas sao feitas juntas de proposito:
 *
 *   o cookie      para que o caminho HTTP real seja exercitado, decodificacao
 *                 inclusive;
 *   o contexto    porque instancias `scoped` nao sao recicladas entre as
 *                 requisicoes de um mesmo teste (so o worker de fila e o
 *                 Octane as reciclam), entao um estado resolvido antes
 *                 sobreviveria ate a requisicao seguinte.
 */
trait InteractsWithConsent
{
    /**
     * @return $this
     */
    protected function withConsent(ConsentState $estado): static
    {
        $agora = Carbon::now();

        app(ConsentContext::class)->set($estado, $agora);

        return $this->withCookies([
            ConsentCookie::name() => ConsentCookie::encode($estado, $agora),
        ]);
    }

    /**
     * @return $this
     */
    protected function acceptingAnalytics(): static
    {
        return $this->withConsent(ConsentState::Accepted);
    }

    /**
     * @return $this
     */
    protected function rejectingAnalytics(): static
    {
        return $this->withConsent(ConsentState::Rejected);
    }

    /**
     * Devolve o contexto ao estado nao resolvido, para que a proxima requisicao
     * releia o cookie. Necessario quando um mesmo teste muda de decisao no meio.
     */
    protected function forgetResolvedConsent(): void
    {
        app(ConsentContext::class)->forget();
    }
}
