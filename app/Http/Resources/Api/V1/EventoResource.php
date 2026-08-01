<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

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
            'image_url' => $this->image_path ? Storage::url($this->image_path) : null,
            'banner_image_url' => $this->banner_image_path ? Storage::url($this->banner_image_path) : null,
            'is_featured' => (bool) $this->is_featured,
            'capacidade_expositores' => $this->capacidade_expositores,
            'vagas_restantes' => $this->vagas_restantes,
            'expositores' => ExpositorResource::collection($this->whenLoaded('expositores')),
        ];
    }
}
