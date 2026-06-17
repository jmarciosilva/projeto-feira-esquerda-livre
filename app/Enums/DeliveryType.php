<?php

namespace App\Enums;

enum DeliveryType: string
{
    case Retirada = 'retirada';
    case Entrega  = 'entrega';

    public function label(): string
    {
        return match($this) {
            self::Retirada => 'Retirar no local',
            self::Entrega  => 'Receber em casa',
        };
    }

    public function emoji(): string
    {
        return match($this) {
            self::Retirada => '📦',
            self::Entrega  => '🚚',
        };
    }
}
