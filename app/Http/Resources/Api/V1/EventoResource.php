<?php

namespace App\Http\Resources\Api\V1;

use App\Support\PublicUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Event */
class EventoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'description' => $this->description,
            'city' => $this->city,
            'state' => $this->state,
            'address' => $this->address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'image_url' => PublicUrl::for($this->image_path),
            'banner_image_url' => PublicUrl::for($this->banner_image_path),
            'is_featured' => (bool) $this->is_featured,
            'capacidade_expositores' => $this->capacidade_expositores,
            'vagas_restantes' => $this->vagas_restantes,
            'expositores' => ExpositorResource::collection($this->whenLoaded('expositores')),
        ];
    }
}
