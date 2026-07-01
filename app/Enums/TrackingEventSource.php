<?php

namespace App\Enums;

enum TrackingEventSource: string
{
    case CarrierApi = 'carrier_api';
    case Manual     = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::CarrierApi => 'Transportadora',
            self::Manual     => 'Manual',
        };
    }
}
