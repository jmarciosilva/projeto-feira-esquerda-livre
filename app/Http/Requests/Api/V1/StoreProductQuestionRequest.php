<?php

namespace App\Http\Requests\Api\V1;

use App\Actions\Catalog\Contexto;
use App\Actions\Catalog\ResolveProductOffer;
use App\Models\Product;
use App\Models\ProductOffer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * CAT-DOM-02E — a pergunta passou a exigir contexto comercial.
 *
 * `product_offer_id` é **opcional**, e continuará opcional enquanto multi-oferta
 * estiver desabilitada: torná-lo obrigatório agora quebraria todo cliente que já
 * chama este endpoint. A compatibilidade é deliberada e datada — quando a
 * aplicação habilitar multi-oferta, uma fase futura pode reavaliar.
 *
 * O que **não** é opcional é a inequivocidade. As três situações:
 *
 * ```text
 * campo informado     → valida que a oferta é DESTE produto; se não for, 422
 * campo ausente + 1 oferta   → resolve pela cardinalidade determinística
 * campo ausente + 0 ou >1    → 422, nunca infere
 * ```
 *
 * Nada de `first()`, `expositor_id`, delegação canônica, ordem de criação, menor
 * ou maior id, nem `ofertaVigente`. Esta última merece a menção explícita porque
 * o resto da API a usa: ela ordena por preço e devolve a oferta mais barata, o
 * que é resolução legítima para *exibir* preço e péssima para *endereçar* uma
 * pergunta — mandaria o cliente falar com um vendedor que ele não escolheu.
 */
class StoreProductQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'question' => ['required', 'string', 'min:5', 'max:500'],
            'product_offer_id' => ['nullable', 'integer', 'exists:product_offers,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            if ($this->resolverOferta() === null) {
                $validator->errors()->add(
                    'product_offer_id',
                    'O contexto da oferta é obrigatório para registrar esta pergunta.',
                );
            }
        });
    }

    /**
     * A oferta que receberá a pergunta, ou `null` quando não houver uma só
     * resposta possível — e nesse caso a requisição é recusada, não adivinhada.
     */
    public function resolverOferta(): ?ProductOffer
    {
        $produto = $this->route('product');

        if (! $produto instanceof Product) {
            return null;
        }

        // Mesma regra do carrinho e do material de divulgação, dita uma vez só
        // (CAT-DOM-02G). `Historico` porque uma pergunta pode ser dirigida a uma
        // oferta que o lojista recolheu depois — quem pode respondê-la é assunto
        // da autorização (D-02F-4), não da resolução.
        return app(ResolveProductOffer::class)(
            $produto,
            $this->input('product_offer_id'),
            Contexto::Historico,
        );
    }
}
