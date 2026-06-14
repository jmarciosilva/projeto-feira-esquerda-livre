<?php

namespace App\Enums;

enum PriceType: string
{
    case Fixo        = 'fixo';
    case PorHora     = 'por_hora';
    case PorSessao   = 'por_sessao';
    case SobConsulta = 'sob_consulta';

    public function label(): string
    {
        return match($this) {
            self::Fixo        => 'Preço fixo',
            self::PorHora     => 'Por hora',
            self::PorSessao   => 'Por sessão',
            self::SobConsulta => 'A combinar',
        };
    }
}
