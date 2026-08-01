<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\OrderSplit */
class OrderSplitResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status?->value,
            'gross_amount' => (float) $this->gross_amount,
            'commission_amount' => (float) $this->commission_amount,
            'net_amount' => (float) $this->net_amount,
            'confirmed_at' => $this->confirmed_at,
            'order' => $this->whenLoaded('order', fn () => [
                'reference' => $this->order->reference,
                'customer_name' => $this->order->customer_name,
                'customer_whatsapp' => $this->order->customer_whatsapp,
                'items' => OrderItemResource::collection($this->order->relationLoaded('items') ? $this->order->items : []),
            ]),
            'expositor' => $this->whenLoaded('expositor', fn () => [
                'id' => $this->expositor->id,
                'name' => $this->expositor->name,
                'whatsapp' => $this->expositor->whatsapp,
                'pix_chave' => $this->expositor->pix_chave,
                'pix_tipo' => $this->expositor->pix_tipo,
            ]),
            'shipping' => $this->whenLoaded('shipping', fn () => $this->shipping ? [
                'status' => $this->shipping->status?->value,
                'carrier' => $this->shipping->carrier,
                'tracking_code' => $this->shipping->tracking_code,
            ] : null),
        ];
    }
}
