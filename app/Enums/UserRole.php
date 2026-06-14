<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin   = 'admin';
    case Editor  = 'editor';
    case Lojista = 'lojista';
    case User    = 'user';

    public function label(): string
    {
        return match($this) {
            self::Admin   => 'Administrador',
            self::Editor  => 'Editor',
            self::Lojista => 'Lojista',
            self::User    => 'Usuário',
        };
    }
}
