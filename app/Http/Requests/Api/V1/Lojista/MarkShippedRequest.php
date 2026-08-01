<?php

namespace App\Http\Requests\Api\V1\Lojista;

use Illuminate\Foundation\Http\FormRequest;

class MarkShippedRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'carrier' => ['required', 'string', 'max:100'],
            'tracking_code' => ['required', 'string', 'max:100'],
            'shipped_at' => ['required', 'date', 'before_or_equal:today'],
        ];
    }
}
