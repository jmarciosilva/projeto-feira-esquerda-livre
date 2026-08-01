<?php

namespace App\Http\Resources\Api\V1\Lojista;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\ProductQuestion */
class PerguntaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'question' => $this->question,
            'answer' => $this->answer,
            'is_visible' => (bool) $this->is_visible,
            'answered_at' => $this->answered_at,
            'created_at' => $this->created_at,
            'product' => $this->whenLoaded('product', fn () => [
                'id' => $this->product->id,
                'name' => $this->product->name,
            ]),
            'asker_name' => $this->whenLoaded('user', fn () => $this->user?->name),
        ];
    }
}
