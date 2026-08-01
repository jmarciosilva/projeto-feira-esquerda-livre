<?php

namespace App\Http\Requests\Api\V1\Lojista;

use Illuminate\Foundation\Http\FormRequest;

class AnswerQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'answer' => ['required', 'string', 'min:2', 'max:2000'],
        ];
    }
}
