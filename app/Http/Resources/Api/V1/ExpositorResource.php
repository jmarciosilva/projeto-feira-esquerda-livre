<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

/** @mixin \App\Models\Expositor */
class ExpositorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'eixos' => $this->eixos,
            'logo_url' => $this->logo_path ? Storage::url($this->logo_path) : null,
            'image_url' => $this->image_path ? Storage::url($this->image_path) : null,
            'city' => $this->city,
            'state' => $this->state,
            'whatsapp' => $this->whatsapp,
            'instagram_url' => $this->instagram_url,
            'facebook_url' => $this->facebook_url,
            'is_active' => (bool) $this->is_active,
        ];
    }
}
