<?php

namespace App\Enums;

enum SolicitacaoStatus: string
{
    case Pendente  = 'pendente';
    case Aprovado  = 'aprovado';
    case Bloqueado = 'bloqueado';

    public function label(): string
    {
        return match($this) {
            self::Pendente  => 'Pendente',
            self::Aprovado  => 'Aprovado',
            self::Bloqueado => 'Bloqueado',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Pendente  => 'yellow',
            self::Aprovado  => 'green',
            self::Bloqueado => 'red',
        };
    }
}
