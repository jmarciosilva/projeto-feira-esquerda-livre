<?php

namespace App\Enums;

enum RecipientType: string
{
    case AllSubscribers   = 'all_subscribers';
    case Customers        = 'customers';
    case CustomersActive  = 'customers_active';
    case SegmentManual    = 'segment_manual';

    public function label(): string
    {
        return match ($this) {
            self::AllSubscribers  => 'Todos os assinantes da newsletter',
            self::Customers       => 'Todos os clientes do marketplace',
            self::CustomersActive => 'Clientes ativos (compraram nos últimos 60 dias)',
            self::SegmentManual   => 'Lista manual de e-mails',
        };
    }
}
