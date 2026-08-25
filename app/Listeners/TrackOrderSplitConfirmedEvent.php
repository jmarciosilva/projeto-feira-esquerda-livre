<?php

namespace App\Listeners;

use App\CustomerIntelligence\Enums\EventName;
use App\CustomerIntelligence\Facades\CustomerIntelligence;
use App\Events\OrderSplitConfirmed;

class TrackOrderSplitConfirmedEvent
{
    public function handle(OrderSplitConfirmed $event): void
    {
        $split = $event->split;

        try {
            CustomerIntelligence::track(EventName::PedidoPagamentoConfirmado, [
                'pedido_id' => $split->order_id,
                'split_id' => $split->id,
                'valor_recebido' => (float) $split->gross_amount,
                'comissao' => (float) $split->commission_amount,
            ], $split);
        } catch (\Throwable $exception) {
            report($exception);
        }
    }
}
