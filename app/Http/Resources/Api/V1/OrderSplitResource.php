<?php

namespace App\Http\Resources\Api\V1;

use App\Models\OrderSplit;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin OrderSplit */
class OrderSplitResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status?->value,
            'gross_amount' => (float) $this->gross_amount,
            'commission_percent' => (float) $this->commission_percent,
            'commission_amount' => (float) $this->commission_amount,
            'net_amount' => (float) $this->net_amount,
            // Nulo quando a divisao do frete entre lojas nao e conhecida —
            // ver a migration `add_shipping_amount_to_order_splits`.
            'shipping_amount' => $this->shipping_amount === null ? null : (float) $this->shipping_amount,
            'confirmed_at' => $this->confirmed_at,
            'order' => $this->whenLoaded('order', fn () => [
                'reference' => $this->order->reference,
                'customer_name' => $this->order->customer_name,
                'customer_whatsapp' => $this->order->customer_whatsapp,
                'items' => OrderItemResource::collection($this->order->relationLoaded('items') ? $this->order->items : []),
            ]),
            // O split e historico: ele sobrevive ao cadastro do vendedor. O
            // nome vem do snapshot gravado na compra; os dados de contato e
            // pagamento so existem enquanto a loja existir.
            // `whenLoaded` omite a chave quando a relacao carregada e nula, e
            // isso apagaria `expositor` do JSON para todo pedido de loja
            // excluida — quebra de contrato para o app. A condicao passa a ser
            // "a relacao foi carregada?", e o nome cai no snapshot.
            'expositor' => $this->when($this->resource->relationLoaded('expositor'), fn () => [
                'id' => $this->expositor?->id,
                'name' => $this->expositor?->name ?? $this->expositor_name,
                'whatsapp' => $this->expositor?->whatsapp,
                'pix_chave' => $this->expositor?->pix_chave,
                'pix_tipo' => $this->expositor?->pix_tipo,
            ]),
            'shipping' => $this->whenLoaded('shipping', fn () => $this->shipping ? [
                'status' => $this->shipping->status?->value,
                'carrier' => $this->shipping->carrier,
                'tracking_code' => $this->shipping->tracking_code,
            ] : null),
        ];
    }
}
