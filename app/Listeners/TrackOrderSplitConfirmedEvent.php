<?php

namespace App\Listeners;

use App\Events\OrderSplitConfirmed;
use JmfSystem\CustomerIntelligence\Facades\CustomerIntelligence;

class TrackOrderSplitConfirmedEvent
{
    public function handle(OrderSplitConfirmed $event): void
    {
        $split = $event->split;

        try {
            CustomerIntelligence::track('pedido.pagamento_confirmado', [
                'pedido_id' => $split->order_id,
                'split_id' => $split->id,
                'valor_recebido' => (float) $split->gross_amount,
                'comissao' => (float) $split->commission_amount,
            ]);
        } catch (\Throwable $exception) {
            report($exception);
        }
    }
}
