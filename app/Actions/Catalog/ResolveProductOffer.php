<?php

namespace App\Actions\Catalog;

use App\Models\Product;
use App\Models\ProductOffer;

/**
 * CAT-DOM-02G — qual oferta deste produto, sem adivinhar.
 *
 * ## O problema que ele resolve
 *
 * Preço, estoque, loja, imagem e FAQ moram na oferta. Um fluxo comercial que
 * recebe apenas `product_id` precisa, em algum momento, decidir **qual** oferta
 * — e enquanto cada produto tem uma só, qualquer forma de decidir dá a resposta
 * certa. `first()`, `orderBy('id')`, `ofertaVigente`: todas funcionam hoje, e
 * todas viram escolha de vendedor no dia em que o segundo aparecer.
 *
 * Pior que errado: seria uma **decisão de produto tomada por acidente**. Dizer
 * "a mais barata ganha" ou "a mais antiga ganha" é a regra de um *buy box*, e
 * ninguém a autorizou — ela apareceria como efeito colateral de um `->first()`
 * escrito quando o mundo era 1:1.
 *
 * ## O contrato
 *
 * ```text
 * id informado            → valida que a oferta é DESTE produto; se não for, nula
 * id ausente + 1 oferta   → resolve pela cardinalidade determinística
 * id ausente + 0 ou >1    → nula, e quem chamou decide como recusar
 * ```
 *
 * Devolver `null` em vez de lançar é deliberado: o `FormRequest` transforma isso
 * em 422 com erro de campo, o controller em 403 ou 404 — cada superfície recusa
 * na convenção dela, e o seletor não escolhe o formato da recusa.
 *
 * ## O que ele nunca usa
 *
 * `first()` · `latest()` · `oldest()` · `orderBy('id')` · `ofertaVigente()` ·
 * `products.expositor_id` · `canonical_delegate_expositor_id`.
 *
 * `ofertaVigente` merece a menção explícita porque o resto da aplicação a usa
 * legitimamente para **exibir** — ela ordena por preço e devolve a mais barata,
 * o que é razoável num card de vitrine e é uma regra de *buy box* quando decide
 * de quem o cliente compra.
 *
 * ## Contexto: comprar ≠ consultar histórico
 *
 * `Contexto::Compra` exige oferta **vigente** — não se compra de loja fechada
 * nem item recolhido. `Contexto::Historico` aceita oferta inativa: o pedido, a
 * matrícula e o comprovante apontam para o que foi vendido, e não para o que
 * ainda está à venda (D-02G-4).
 */
final class ResolveProductOffer
{
    public function __invoke(
        Product $product,
        int|string|null $ofertaId = null,
        Contexto $contexto = Contexto::Compra,
    ): ?ProductOffer {
        $candidatas = $product->offers()->with('expositor')->get();

        if ($ofertaId !== null) {
            $escolhida = $candidatas->firstWhere('id', (int) $ofertaId);

            // Oferta de outro produto: recusa, nunca substituição silenciosa —
            // trocar pela "oferta certa deste produto" seria decidir pelo
            // cliente de qual loja ele quis comprar.
            return $escolhida !== null && $this->aceita($escolhida, $contexto)
                ? $escolhida
                : null;
        }

        $elegiveis = $candidatas->filter(fn (ProductOffer $o) => $this->aceita($o, $contexto));

        // Exatamente uma, ou nenhuma. Nunca "a primeira das várias".
        return $elegiveis->count() === 1 ? $elegiveis->first() : null;
    }

    private function aceita(ProductOffer $offer, Contexto $contexto): bool
    {
        return $contexto === Contexto::Historico || $offer->isVigente();
    }
}
