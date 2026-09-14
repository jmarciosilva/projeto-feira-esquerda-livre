<?php

namespace App\CatalogIntelligence\Support;

use App\CatalogIntelligence\DTOs\GuardedPrompt;
use App\CatalogIntelligence\DTOs\ListingContext;
use App\CatalogIntelligence\Enums\ProviderInstruction;

/**
 * A fronteira de autoridade — gate **S-1**.
 *
 * CAT-06F. Recebe o `ListingContext` que `GenerateListingSuggestion` completou e
 * devolve um `GuardedPrompt`, em que instrução, contexto recuperado e dado do
 * lojista ocupam canais separados (D-CAT-06F-1).
 *
 * ## O que ela é, e o que não é
 *
 * É uma **classificação por origem**: decide em que canal cada campo do contexto
 * viaja, e só isso.
 *
 * Não é detector de ataque, classificador de intenção nem filtro de frase — não
 * lê o conteúdo de campo nenhum. Um nome de item que peça para desconsiderar as
 * regras e um título de livro com as mesmas palavras chegam ao mesmo canal, do
 * mesmo jeito, intactos. A proteção não depende de reconhecer a frase, e por
 * isso não envelhece na primeira frase que ninguém previu.
 *
 * ## Como classifica (D-CAT-06F-3)
 *
 * Pela forma que o próprio `ListingContext` já tem:
 *
 * - **contexto recuperado** — `knowledge` e `similar_items`, que a aplicação
 *   buscou e que entram no contexto por cópia (`comConhecimento()`,
 *   `comSemelhantes()`);
 * - **dado do lojista** — todo o resto, que descreve o item em cadastro.
 *
 * O canal de contexto é **lista de permissão**. Uma chave nova em
 * `ListingContext::toArray()` cai no canal de dado sem que ninguém precise
 * lembrar dela: o lado não confiável é o padrão, e nunca o de instrução.
 *
 * A instrução não vem do contexto nem de parâmetro — é fixada aqui
 * (D-CAT-06F-2). Não há caminho de entrada por onde um texto chegue a ela.
 *
 * ## O que ela não faz, de propósito (D-CAT-06F-4)
 *
 * - **Não redige.** PII é a C-2, do `FreeTextRedactor`. As duas peças não se
 *   conhecem: `GenerateListingSuggestion` aplica primeiro este guard e, sobre o
 *   `GuardedPrompt` que ele devolve, o `GuardedPromptRedactor`.
 * - **Não escapa, não apara e não descarta.** O valor sai byte a byte como
 *   entrou, vazio e nulo inclusive.
 * - **Não lança e não registra.** Não há `ListingContext` inválido para
 *   recusar — ele já nasce pelo sanitizer —, e registrar seria gravar o texto que
 *   ela existe para manter no seu lugar.
 * - **Não fala com provider.** Quem a chama é `GenerateListingSuggestion`, e só
 *   quando vai consultar um provider disponível; o `GuardedPrompt` devolvido
 *   ainda passa pelo `GuardedPromptRedactor` antes de chegar a
 *   `CatalogAiProvider::suggest()`.
 */
class PromptGuard
{
    /** As chaves de `ListingContext::toArray()` que a aplicação recuperou. */
    private const CONTEXTO_RECUPERADO = ['knowledge', 'similar_items'];

    public function __invoke(ListingContext $contexto): GuardedPrompt
    {
        $campos = $contexto->toArray();
        $recuperado = array_intersect_key($campos, array_flip(self::CONTEXTO_RECUPERADO));

        return new GuardedPrompt(
            instruction: ProviderInstruction::SuggestListing,
            context: $recuperado,
            data: array_diff_key($campos, $recuperado),
        );
    }
}
