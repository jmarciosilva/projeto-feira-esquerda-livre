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
            'shipping_total' => ['nullable', 'numeric', 'min:0'],
            'shipping_note' => ['nullable', 'string'],
        ];
    }
}
