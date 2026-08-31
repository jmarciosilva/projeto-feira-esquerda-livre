<?php

namespace App\Http\Requests\Api\V1;

use App\Actions\Catalog\Contexto;
use App\Actions\Catalog\ResolveProductOffer;
use App\Models\Product;
use App\Models\ProductOffer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * CAT-DOM-02G — o que entra no carrinho é uma oferta, não um produto.
 *
 * O contrato ganhou `product_offer_id`, **opcional**, no mesmo padrão que a
 * CAT-DOM-02E adotou para as perguntas: informado, é validado contra o produto;
 * ausente, só resolve quando existe exatamente uma oferta vigente; com zero ou
 * mais de uma, a requisição é recusada com 422.
 *
 * Opcional para não quebrar nenhum cliente — o app continua podendo mandar só
 * `product_id`. O que acabou foi a resolução por `ofertaVigente`, que ordena por
 * preço e devolve a mais barata: aceitável para *exibir* um card de vitrine, e
 * uma regra de **buy box** quando decide de qual loja o cliente compra. Ninguém
 * autorizou essa regra, e ela não deve nascer de um `->first()` (D-02G-2).
 */
class StoreCartItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'product_offer_id' => ['nullable', 'integer', 'exists:product_offers,id'],
            'quantity' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            if ($this->ofertaEscolhida() === null) {
                $validator->errors()->add(
                    'product_offer_id',
                    'Informe a oferta desejada: este item não tem uma única oferta disponível.',
                );
            }
        });
    }

    /**
     * A oferta que vai para o carrinho, ou `null` quando não há uma só resposta
     * possível — e aí a requisição é recusada, nunca adivinhada.
     */
    public function ofertaEscolhida(): ?ProductOffer
    {
        $produto = Product::find($this->input('product_id'));

        if ($produto === null) {
            return null;
        }

        return app(ResolveProductOffer::class)(
            $produto,
            $this->input('product_offer_id'),
            Contexto::Compra,
        );
    }
}
