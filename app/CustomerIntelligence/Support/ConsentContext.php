<?php

namespace App\CustomerIntelligence\Support;

use App\CustomerIntelligence\Enums\ConsentState;
use DateTimeInterface;
use Illuminate\Support\Carbon;

/**
 * Estado do consentimento na requisicao atual.
 *
 * Registrado como `scoped`, e nao `singleton`, pela mesma razao do
 * VisitorContext: sob um servidor de longa duracao um singleton carregaria a
 * escolha de uma pessoa para a requisicao da proxima.
 *
 * A leitura do cookie e preguicosa. Fora do ciclo HTTP — comandos de console,
 * workers de fila — nao ha cookie para ler e o estado permanece Unknown, que
 * nao autoriza coleta. Isso e proposital: quem decide se um evento pode ser
 * coletado e a requisicao onde ele nasce, nao o worker que o grava depois.
 */
class ConsentContext
{
    private ConsentState $state = ConsentState::Unknown;

    private ?Carbon $decidedAt = null;

    private bool $resolved = false;

    public function state(): ConsentState
    {
        $this->resolve();

        return $this->state;
    }

    public function decidedAt(): ?Carbon
    {
        $this->resolve();

        return $this->decidedAt;
    }

    public function allowsAnalytics(): bool
    {
        return $this->state()->allowsAnalytics();
    }

    public function isDecided(): bool
    {
        return $this->state()->isDecided();
    }

    /**
     * Fixa o estado sem consultar o cookie.
     *
     * Usado logo depois de gravar uma decisao: o cookie novo so chega ao
     * navegador na resposta, entao o resto DESTA requisicao precisa enxergar a
     * escolha recem-feita, e nao a anterior.
     */
    public function set(ConsentState $state, ?DateTimeInterface $decidedAt = null): void
    {
        $this->state = $state;
        $this->decidedAt = $decidedAt === null ? null : Carbon::parse($decidedAt);
        $this->resolved = true;
    }

    public function forget(): void
    {
        $this->state = ConsentState::Unknown;
        $this->decidedAt = null;
        $this->resolved = false;
    }

    private function resolve(): void
    {
        if ($this->resolved) {
            return;
        }

        $this->resolved = true;

        $request = request();

        [$this->state, $this->decidedAt] = ConsentCookie::decode(
            $request?->cookie(ConsentCookie::name())
        );
    }
}
