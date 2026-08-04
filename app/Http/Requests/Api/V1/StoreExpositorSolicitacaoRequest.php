<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class StoreExpositorSolicitacaoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nome_loja' => ['required', 'string', 'max:255'],
            'responsavel' => ['required', 'string', 'max:255'],
            'cpf_cnpj' => ['required', 'string', 'max:20'],
            'whatsapp' => ['required', 'string', 'max:20'],
            'email' => ['required', 'email', 'max:255'],
            'instagram_url' => ['required', 'url', 'max:255'],
            'facebook_url' => ['nullable', 'url', 'max:255'],
            'pix_tipo' => ['required', 'string', 'max:20'],
            'pix_chave' => ['required', 'string', 'max:255'],
            'banco_nome' => ['nullable', 'string', 'max:100'],
            'banco_agencia' => ['nullable', 'string', 'max:20'],
            'banco_conta' => ['nullable', 'string', 'max:30'],
            'banco_tipo_conta' => ['nullable', 'string', 'max:20'],
            'descricao' => ['nullable', 'string', 'max:2000'],
            'eixos' => ['nullable', 'array'],
            'eixos.*' => ['in:produto,servico,cuidado'],
        ];
    }

    public function messages(): array
    {
        return [
            'nome_loja.required' => 'Informe o nome da loja.',
            'responsavel.required' => 'Informe o nome do responsável.',
            'cpf_cnpj.required' => 'Informe o CPF ou CNPJ.',
            'whatsapp.required' => 'Informe o WhatsApp.',
            'email.required' => 'Informe o e-mail.',
            'email.email' => 'Informe um e-mail válido.',
            'instagram_url.required' => 'Informe o endereço do Instagram.',
            'instagram_url.url' => 'O endereço do Instagram deve ser uma URL válida.',
            'facebook_url.url' => 'O endereço do Facebook deve ser uma URL válida.',
            'pix_tipo.required' => 'Selecione o tipo da chave PIX.',
            'pix_chave.required' => 'Informe a chave PIX.',
        ];
    }
}
