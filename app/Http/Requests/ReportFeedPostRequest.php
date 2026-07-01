<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReportFeedPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->is_active ?? false;
    }

    public function rules(): array
    {
        return self::baseRules();
    }

    public static function baseRules(string $field = 'reportReason'): array
    {
        return [
            $field => ['required', 'string', 'max:500'],
        ];
    }
}
