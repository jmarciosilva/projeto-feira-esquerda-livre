<?php

namespace App\Enums;

enum Modality: string
{
    case Presencial = 'presencial';
    case Online     = 'online';
    case Ambos      = 'ambos';

    public function label(): string
    {
        return match($this) {
            self::Presencial => 'Presencial',
            self::Online     => 'Online',
            self::Ambos      => 'Presencial e Online',
        };
    }
}
