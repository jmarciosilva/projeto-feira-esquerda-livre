<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_whatsapp' => ['required', 'string', 'max:20'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'delivery_type' => ['required', 'in:retirada,entrega'],
            'customer_address_id' => ['nullable', 'integer', 'exists:customer_addresses,id'],
            // Escolha de frete por loja: qual servico, nunca quanto custa.
            // O preco e resolvido no servidor, recotando as opcoes reais.
            'shipping_options' => ['nullable', 'array'],
            // `distinct` porque duas escolhas para a mesma loja sao ambiguas: o
            // cliente mandaria PAC e SEDEX e o servidor decidiria sozinho qual
            // vale. Em campo economico, ambiguidade se recusa, nao se resolve.
            'shipping_options.*.expositor_id' => ['required_with:shipping_options', 'integer', 'distinct'],
            'shipping_options.*.service_id' => ['required_with:shipping_options', 'string', 'max:60'],

            // Mantido por compatibilidade e DEPRECIADO: nao decide mais nada.
            // Quando enviado, serve so para detectar app desatualizado — se
            // divergir do valor cotado pelo servidor, o pedido e recusado em
            // vez de cobrar do cliente um valor diferente do que ele viu.
            'shipping_total' => ['nullable', 'numeric', 'min:0'],
            'shipping_note' => ['nullable', 'string'],
        ];
    }
}
