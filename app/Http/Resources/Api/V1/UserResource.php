<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\User */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'whatsapp' => $this->whatsapp,
            'role' => $this->role?->value,
            'role_label' => $this->role?->label(),
            'is_active' => (bool) $this->is_active,
            'marketplace_status' => $this->whenLoaded(
                'customerProfile',
                fn () => $this->customerProfile?->marketplace_status?->value
            ),
            'expositor' => $this->whenLoaded(
                'expositor',
                fn () => $this->expositor ? new ExpositorResource($this->expositor) : null
            ),
        ];
    }
}
