<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Order */
class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'reference' => $this->reference,
            'status' => $this->status?->value,
            'delivery_type' => $this->delivery_type?->value,
            'customer_name' => $this->customer_name,
            'customer_whatsapp' => $this->customer_whatsapp,
            'customer_email' => $this->customer_email,
            'address' => [
                'cep' => $this->address_cep,
                'rua' => $this->address_rua,
                'numero' => $this->address_numero,
                'complemento' => $this->address_complemento,
                'bairro' => $this->address_bairro,
                'cidade' => $this->address_cidade,
                'estado' => $this->address_estado,
            ],
            'items_total' => (float) $this->items_total,
            'shipping_total' => (float) $this->shipping_total,
            'shipping_note' => $this->shipping_note,
            'total_amount' => (float) $this->total_amount,
            'payment_method' => $this->payment_method,
            'payment_status' => $this->payment_status,
            'mercado_pago_checkout_url' => $this->mercado_pago_sandbox_init_point ?: $this->mercado_pago_init_point,
            'paid_at' => $this->paid_at,
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'splits' => OrderSplitResource::collection($this->whenLoaded('splits')),
            'created_at' => $this->created_at,
        ];
    }
}
