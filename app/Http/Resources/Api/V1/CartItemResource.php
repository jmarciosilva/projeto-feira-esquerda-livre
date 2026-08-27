<?php

namespace App\Http\Resources\Api\V1;

use App\Models\CartItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin CartItem */
class CartItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'quantity' => $this->quantity,
            'price_snapshot' => (float) $this->price_snapshot,
            'subtotal' => (float) $this->subtotal(),
            // O item do carrinho conhece a oferta comprada: o JSON precisa
            // mostrar o preço e as condições daquela loja.
            'product' => $this->offer
                ? ProductResource::daOferta($this->offer)
                : new ProductResource($this->whenLoaded('product')),
        ];
    }
}
