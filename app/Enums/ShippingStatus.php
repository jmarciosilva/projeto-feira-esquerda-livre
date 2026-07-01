<?php

namespace App\Enums;

enum ShippingStatus: string
{
    case Pending         = 'pending';
    case Shipped         = 'shipped';
    case InTransit       = 'in_transit';
    case OutForDelivery  = 'out_for_delivery';
    case Delivered       = 'delivered';
    case Returned        = 'returned';
    case Failed          = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending        => 'Aguardando Envio',
            self::Shipped        => 'Enviado',
            self::InTransit      => 'Em Trânsito',
            self::OutForDelivery => 'Saiu para Entrega',
            self::Delivered      => 'Entregue',
            self::Returned       => 'Devolvido',
            self::Failed         => 'Problema na Entrega',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending        => 'gray',
            self::Shipped        => 'blue',
            self::InTransit      => 'indigo',
            self::OutForDelivery => 'yellow',
            self::Delivered      => 'green',
            self::Returned       => 'orange',
            self::Failed         => 'red',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Pending        => '📦',
            self::Shipped        => '🚚',
            self::InTransit      => '🔄',
            self::OutForDelivery => '🏃',
            self::Delivered      => '✅',
            self::Returned       => '↩️',
            self::Failed         => '⚠️',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Delivered, self::Returned, self::Failed], true);
    }

    /** @return array<int, self> — ordered timeline for display */
    public static function timeline(): array
    {
        return [
            self::Pending,
            self::Shipped,
            self::InTransit,
            self::OutForDelivery,
            self::Delivered,
        ];
    }

    public static function fromMelhorEnvio(string $meStatus): self
    {
        return match (strtolower($meStatus)) {
            'posted', 'submitted'     => self::Shipped,
            'in_transit', 'transit'   => self::InTransit,
            'out_for_delivery'        => self::OutForDelivery,
            'delivered'               => self::Delivered,
            'undelivered', 'returned' => self::Returned,
            'lost', 'error'           => self::Failed,
            default                   => self::InTransit,
        };
    }
}
