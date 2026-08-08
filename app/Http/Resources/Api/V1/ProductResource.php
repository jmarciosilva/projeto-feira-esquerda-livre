<?php

namespace App\Http\Resources\Api\V1;

use App\Support\PublicUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\Product */
class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'item_type' => $this->item_type?->value,
            'description' => $this->description,
            'price' => (float) $this->price,
            'price_type' => $this->price_type?->value,
            'modality' => $this->modality?->value,
            'duration_min' => $this->duration_min,
            'has_stock' => (bool) $this->has_stock,
            'stock_quantity' => $this->stock_quantity,
            'is_digital' => (bool) $this->is_digital,
            'is_featured' => (bool) $this->is_featured,
            'is_active' => (bool) $this->is_active,
            'weight' => $this->weight ? (float) $this->weight : null,
            'height' => $this->height ? (float) $this->height : null,
            'width' => $this->width ? (float) $this->width : null,
            'length' => $this->length ? (float) $this->length : null,
            'main_image_url' => $this->main_image_url,
            'images' => collect($this->images ?? [])->map(fn (array $image) => [
                'thumbnail_url' => isset($image['thumb']) ? PublicUrl::for($image['thumb']) : null,
                'medium_url' => isset($image['medium']) ? PublicUrl::for($image['medium']) : null,
            ])->values(),
            'expositor' => $this->whenLoaded('expositor', fn () => new ExpositorResource($this->expositor)),
            'category' => $this->whenLoaded('category', fn () => $this->category ? [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ] : null),
            'faqs' => $this->whenLoaded('faqs', fn () => $this->faqs->map(fn ($faq) => [
                'question' => $faq->question,
                'answer' => $faq->answer,
            ])),
            'created_at' => $this->created_at,
        ];
    }
}
