<?php

namespace App\Enums;

enum OrderSplitStatus: string
{
    case Pendente   = 'pendente';
    case Confirmado = 'confirmado';

    public function label(): string
    {
        return match($this) {
            self::Pendente   => 'Aguardando confirmação',
            self::Confirmado => 'Pagamento confirmado',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Pendente   => 'yellow',
            self::Confirmado => 'green',
        };
    }
}
