<?php

namespace App\Http\Resources\Api\V1;

use App\Support\PublicUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\FeedPost */
class FeedPostResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type?->value,
            'type_label' => $this->type?->label(),
            'content' => $this->content,
            'images' => collect($this->images ?? [])->map(fn (array $image) => [
                'thumbnail_url' => isset($image['thumb']) ? PublicUrl::for($image['thumb']) : null,
                'medium_url' => isset($image['medium']) ? PublicUrl::for($image['medium']) : null,
            ])->values(),
            'expositor' => $this->whenLoaded('expositor', fn () => [
                'id' => $this->expositor->id,
                'name' => $this->expositor->name,
                'slug' => $this->expositor->slug,
                'logo_url' => PublicUrl::for($this->expositor->logo_path),
            ]),
            'likes_count' => $this->likes_count ?? $this->likes()->count(),
            'comments_count' => $this->comments_count ?? $this->comments()->visible()->count(),
            'liked_by_me' => $request->user() ? $this->isLikedBy($request->user()) : false,
            'created_at' => $this->created_at,
        ];
    }
}
