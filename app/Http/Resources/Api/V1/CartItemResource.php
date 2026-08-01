<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\CartItem */
class CartItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'quantity' => $this->quantity,
            'price_snapshot' => (float) $this->price_snapshot,
            'subtotal' => (float) $this->subtotal(),
            'product' => new ProductResource($this->whenLoaded('product')),
        ];
    }
}
