<?php

namespace App\Enums;

enum AvaEnrollmentStatus: string
{
    case Active    = 'active';
    case Expired   = 'expired';
    case Cancelled = 'cancelled';
    case Refunded  = 'refunded';

    public function label(): string
    {
        return match($this) {
            self::Active    => 'Ativo',
            self::Expired   => 'Expirado',
            self::Cancelled => 'Cancelado',
            self::Refunded  => 'Reembolsado',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Active    => 'green',
            self::Expired   => 'yellow',
            self::Cancelled => 'gray',
            self::Refunded  => 'red',
        };
    }

    public function isAccessible(): bool
    {
        return $this === self::Active;
    }
}
