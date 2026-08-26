<?php

namespace App\Http\Requests\Api\V1\Lojista;

use Illuminate\Foundation\Http\FormRequest;

class ProdutoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isProduto = $this->input('item_type', 'produto') === 'produto';

        return [
            'item_type' => ['required', 'in:produto,servico,cuidado'],
            'name' => ['required', 'string', 'max:255'],
            'short_description' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'weight' => $isProduto ? ['nullable', 'numeric', 'min:0.001'] : ['prohibited'],
            'height' => $isProduto ? ['nullable', 'numeric', 'min:0.01'] : ['prohibited'],
            'width' => $isProduto ? ['nullable', 'numeric', 'min:0.01'] : ['prohibited'],
            'length' => $isProduto ? ['nullable', 'numeric', 'min:0.01'] : ['prohibited'],
            'price_type' => ['nullable', 'in:fixo,por_hora,por_sessao,sob_consulta'],
            'modality' => ['nullable', 'in:presencial,online,ambos'],
            'duration_min' => ['nullable', 'integer', 'min:1', 'max:480'],
            'category_id' => ['nullable', 'exists:content_categories,id'],
            'has_stock' => ['nullable', 'boolean'],
            'stock_quantity' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
            'is_digital' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'images' => ['nullable', 'array', 'max:4'],
            'images.*' => ['image', 'mimes:jpg,jpeg,png,gif,webp', 'max:4096'],
            'remove_image_indexes' => ['nullable', 'array'],
            'remove_image_indexes.*' => ['integer', 'min:0'],
            'faqs' => ['nullable', 'array', 'max:15'],
            'faqs.*.question' => ['nullable', 'string'],
            'faqs.*.answer' => ['nullable', 'string'],
        ];
    }
}
