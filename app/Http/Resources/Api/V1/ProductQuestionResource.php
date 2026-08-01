<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin \App\Models\ProductQuestion */
class ProductQuestionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'question' => $this->question,
            'answer' => $this->answer,
            'asker_first_name' => $this->askerFirstName(),
            'answered_at' => $this->answered_at,
            'created_at' => $this->created_at,
        ];
    }
}
