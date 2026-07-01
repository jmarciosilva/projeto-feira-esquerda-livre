<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Gerente = 'gerente';
    case Supervisor = 'supervisor';
    case Editor = 'editor';
    case Lojista = 'lojista';
    case User = 'user';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::Gerente => 'Gerente',
            self::Supervisor => 'Supervisor',
            self::Editor => 'Editor',
            self::Lojista => 'Lojista',
            self::User => 'Usuário',
        };
    }

    public function spatieRole(): string
    {
        return match ($this) {
            self::Admin => 'administrador',
            self::User => 'cliente',
            default => $this->value,
        };
    }

    public function isInternal(): bool
    {
        return in_array($this, [
            self::Admin,
            self::Gerente,
            self::Supervisor,
            self::Editor,
        ], true);
    }
}
