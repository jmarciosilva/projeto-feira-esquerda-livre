<?php

namespace App\Enums;

enum OrderStatus: string
{
    case AguardandoPagamento = 'aguardando_pagamento';
    case PagamentoConfirmado = 'pagamento_confirmado';
    case Concluido           = 'concluido';
    case Cancelado           = 'cancelado';

    public function label(): string
    {
        return match($this) {
            self::AguardandoPagamento => 'Aguardando Pagamento',
            self::PagamentoConfirmado => 'Pagamento Confirmado',
            self::Concluido           => 'Concluído',
            self::Cancelado           => 'Cancelado',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::AguardandoPagamento => 'yellow',
            self::PagamentoConfirmado => 'blue',
            self::Concluido           => 'green',
            self::Cancelado           => 'red',
        };
    }
}
