<?php

namespace App\Http\Requests\Api\V1\Lojista;

use Illuminate\Foundation\Http\FormRequest;

class LojaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'description' => ['nullable', 'string', 'max:2000'],
            'whatsapp' => ['nullable', 'string', 'max:20'],
            'instagram_url' => ['nullable', 'url', 'max:500'],
            'facebook_url' => ['nullable', 'url', 'max:500'],
            'website_url' => ['nullable', 'url', 'max:500'],
            'zipcode' => ['nullable', 'string', 'max:9'],
            'street' => ['nullable', 'string', 'max:255'],
            'number' => ['nullable', 'string', 'max:20'],
            'district' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:2'],
            'slug' => ['nullable', 'string', 'max:255'],
            'eixos' => ['nullable', 'array'],
            'eixos.*' => ['in:produto,servico,cuidado'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif,webp,svg', 'max:2048'],
            'banner' => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif,webp,svg', 'max:4096'],
            'banco_nome' => ['nullable', 'string', 'max:100'],
            'banco_agencia' => ['nullable', 'string', 'max:20'],
            'banco_conta' => ['nullable', 'string', 'max:30'],
            'banco_tipo_conta' => ['nullable', 'string', 'max:20'],
            'pix_tipo' => ['nullable', 'string', 'max:20'],
            'pix_chave' => ['nullable', 'string', 'max:255'],
        ];
    }
}
