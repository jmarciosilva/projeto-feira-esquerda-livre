<?php

namespace App\CustomerIntelligence\Support;

use App\CustomerIntelligence\Models\Visitor;
use App\CustomerIntelligence\Models\VisitorSession;

/**
 * Guarda o visitante e a sessao resolvidos para a requisicao atual.
 *
 * Registrado como `scoped` no container, e nao `singleton`: sob servidores de
 * longa duracao (Octane) um singleton vazaria o visitante de uma requisicao
 * para a seguinte.
 *
 * Preenchido pelo middleware TrackVisitorSession e consultado pelo
 * CustomerIntelligenceService, que assim consegue gravar um evento sem que
 * quem chama precise carregar visitante e sessao pela aplicacao inteira.
 *
 * Fica vazio fora do ciclo HTTP — em comandos de console e em workers de fila,
 * onde nao existe cookie para resolver.
 */
class VisitorContext
{
    private ?VisitorSession $session = null;

    public function setSession(VisitorSession $session): void
    {
        $this->session = $session;
    }

    public function session(): ?VisitorSession
    {
        return $this->session;
    }

    public function visitor(): ?Visitor
    {
        return $this->session?->visitor;
    }

    public function isResolved(): bool
    {
        return $this->session !== null;
    }

    public function forget(): void
    {
        $this->session = null;
    }
}
