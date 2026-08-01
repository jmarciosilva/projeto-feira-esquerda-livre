<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\OrderMessage */
class OrderMessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'body' => $this->body,
            'sender_id' => $this->sender_id,
            'sender_name' => $this->whenLoaded('sender', fn () => $this->sender?->name),
            'is_mine' => $this->sender_id === $request->user()?->id,
            'read_at' => $this->read_at,
            'created_at' => $this->created_at,
        ];
    }
}
