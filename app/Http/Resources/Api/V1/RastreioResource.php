<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\OrderShipping */
class RastreioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'status' => $this->status?->value,
            'carrier' => $this->carrier,
            'service_name' => $this->service_name,
            'tracking_code' => $this->tracking_code,
            'shipped_at' => $this->shipped_at,
            'delivered_at' => $this->delivered_at,
            'estimated_delivery_date' => $this->estimatedDeliveryDate(),
            'carrier_tracking_url' => $this->carrierTrackingUrl(),
            'expositor' => $this->whenLoaded('expositor', fn () => [
                'name' => $this->expositor->name,
            ]),
            'events' => $this->whenLoaded('trackingEvents', fn () => $this->trackingEvents->map(fn ($event) => [
                'status' => $event->status,
                'description' => $event->description,
                'location' => $event->location,
                'happened_at' => $event->happened_at,
            ])),
        ];
    }
}
