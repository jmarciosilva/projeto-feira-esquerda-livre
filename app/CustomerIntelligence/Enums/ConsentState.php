<?php

namespace App\CustomerIntelligence\Enums;

/**
 * Estado do consentimento para analytics.
 *
 * Tres estados, e nao um booleano: "nunca respondeu" e "recusou" produzem o
 * mesmo efeito na coleta, mas nao no que a interface deve fazer. Achatar os
 * dois em `false` esconderia justamente a diferenca que decide se o banner
 * aparece — e reapresentar a pergunta para quem ja disse nao seria pressao,
 * nao consentimento.
 */
enum ConsentState: string
{
    case Unknown = 'unknown';
    case Accepted = 'accepted';
    case Rejected = 'rejected';

    /**
     * O unico estado que autoriza coleta. Escrito como comparacao positiva de
     * proposito: o padrao de qualquer valor inesperado e nao coletar.
     */
    public function allowsAnalytics(): bool
    {
        return $this === self::Accepted;
    }

    public function isDecided(): bool
    {
        return $this !== self::Unknown;
    }

    public function label(): string
    {
        return match ($this) {
            self::Unknown => 'Ainda não respondido',
            self::Accepted => 'Aceito',
            self::Rejected => 'Recusado',
        };
    }

    /**
     * Converte um valor vindo de fora (cookie, formulario) sem nunca lancar.
     * Entrada corrompida, adulterada ou de uma versao antiga cai em Unknown,
     * que e o estado que nao autoriza nada.
     */
    public static function parse(mixed $valor): self
    {
        return is_string($valor)
            ? (self::tryFrom($valor) ?? self::Unknown)
            : self::Unknown;
    }
}
