<?php

namespace App\Jobs;

use App\Enums\OrderStatus;
use App\Enums\ShippingStatus;
use App\Enums\TrackingEventSource;
use App\Models\OrderShipping;
use App\Services\Shipping\MelhorEnvioService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class TrackShipmentsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public function handle(MelhorEnvioService $service): void
    {
        OrderShipping::query()
            ->whereNotNull('tracking_code')
            ->whereNotIn('status', [
                ShippingStatus::Delivered->value,
                ShippingStatus::Returned->value,
                ShippingStatus::Failed->value,
            ])
            ->with(['trackingEvents', 'order'])
            ->each(function (OrderShipping $shipping) use ($service) {
                $this->syncShipping($shipping, $service);
            });
    }

    private function syncShipping(OrderShipping $shipping, MelhorEnvioService $service): void
    {
        $events = $service->track($shipping->tracking_code);

        if (empty($events)) {
            return;
        }

        $existingHappenedAts = $shipping->trackingEvents
            ->pluck('happened_at')
            ->map(fn ($d) => $d->toDateTimeString())
            ->all();

        $newStatus = $shipping->status;

        foreach ($events as $event) {
            $happenedAt = $event['happened_at'];

            if (in_array($happenedAt, $existingHappenedAts, true)) {
                continue;
            }

            $normalized = ShippingStatus::fromMelhorEnvio($event['status']);

            $shipping->trackingEvents()->create([
                'status'      => $normalized->value,
                'description' => $event['description'],
                'location'    => $event['location'],
                'happened_at' => $happenedAt,
                'source'      => TrackingEventSource::CarrierApi,
            ]);

            $newStatus = $normalized;
        }

        if ($newStatus !== $shipping->status) {
            $updates = ['status' => $newStatus];

            if ($newStatus === ShippingStatus::Delivered) {
                $updates['delivered_at'] = now();
            }

            $shipping->update($updates);

            // Se todas as lojas do pedido entregaram, marca pedido como concluído
            $this->checkOrderCompletion($shipping);
        }
    }

    private function checkOrderCompletion(OrderShipping $shipping): void
    {
        $order = $shipping->order;

        if (! $order) {
            return;
        }

        $allDelivered = $order->shippings()
            ->where('status', '!=', ShippingStatus::Delivered->value)
            ->doesntExist();

        if ($allDelivered && $order->status !== OrderStatus::Concluido) {
            $order->update(['status' => OrderStatus::Concluido]);
        }
    }
}
