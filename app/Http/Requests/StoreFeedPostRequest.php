<?php

namespace App\Http\Requests;

use App\Enums\FeedPostType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFeedPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\FeedPost::class) ?? false;
    }

    public function rules(): array
    {
        return self::baseRules();
    }

    public static function baseRules(): array
    {
        return [
            'type' => ['required', Rule::enum(FeedPostType::class)],
            'content' => ['required', 'string', 'max:500'],
            'upload1' => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:4096'],
            'upload2' => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:4096'],
            'upload3' => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:4096'],
            'upload4' => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:4096'],
        ];
    }
}
