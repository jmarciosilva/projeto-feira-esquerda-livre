<?php

namespace App\CatalogIntelligence\Support;

use App\CatalogIntelligence\DTOs\ListingContext;
use App\CatalogIntelligence\Enums\KnowledgeSufficiency;
use App\CatalogIntelligence\Enums\ListingGap;

/**
 * Há material suficiente para sugerir, ou valeria a pena buscar fora?
 *
 * CAT-06C. É a primeira peça da CAT-06 e **não conhece provider algum** — nem
 * por interface, nem por config, nem por nome. Ela decide *se valeria
 * consultar*; *consultar* é a CAT-06G, depois que o redator (06E) e o guard
 * (06F) existirem.
 *
 * Essa ignorância é o que a torna testável sem um único dublê: não há o que
 * injetar, não há resposta a simular, e o teste da política nunca vai depender
 * de um `Fake` que ainda não existe.
 *
 * ## Lê `lacunas()`; não reconta
 *
 * `ListingContext::lacunas()` foi escrito na CAT-05C exatamente para isto —
 * *"para que a decisão 'há material suficiente?' seja tomada sobre um fato, e
 * não sobre um `empty()` espalhado por quem consome"*.
 *
 * Esta classe chama o método **uma vez** e deriva tudo do array devolvido.
 * Nenhuma propriedade do contexto é lida diretamente: se ela olhasse
 * `knowledge === []` por conta própria, existiriam duas respostas para "o que
 * falta neste item", e elas divergiriam no primeiro ajuste de qualquer um dos
 * lados. `test_a_politica_nao_reconta_lacunas` trava isso na fonte.
 *
 * ## O limiar vem do config, não daqui
 *
 * `config('catalog-intelligence.fallback.minimum_gaps')`, lido **no ponto de
 * uso** e não guardado no construtor — mesmo padrão de
 * `CustomerIntelligence\Support\TrackingPolicy`. Um valor capturado na
 * construção envelheceria sob Octane e tornaria o config decorativo.
 *
 * @see KnowledgeSufficiency  por que o veredito não é booleano
 */
class SuggestionPolicy
{
    /**
     * Padrão de segurança quando o config sumir.
     *
     * Um config ausente não pode ligar fallback sozinho: 5 exige que *todas* as
     * lacunas estejam abertas, que é o mais conservador possível sem desligar a
     * política. Falhar para o lado barato, não para o lado que gasta.
     */
    private const LIMIAR_PADRAO = 5;

    /**
     * O veredito sobre um contexto.
     *
     * Recebe o `ListingContext` inteiro, e não o array de `lacunas()` já
     * calculado. A diferença importa: com o array, qualquer chamador poderia
     * passar uma lista fabricada, e a garantia de fonte única valeria por
     * convenção. Recebendo o contexto, **a política é quem pergunta**, e a
     * fonte única é estrutural.
     */
    public function __invoke(ListingContext $contexto): KnowledgeSufficiency
    {
        $lacunas = $contexto->lacunas();

        if (count($lacunas) < $this->limiar()) {
            return KnowledgeSufficiency::Sufficient;
        }

        return $this->alguemDeForaPodeEscrever($lacunas)
            ? KnowledgeSufficiency::ExternalMayHelp
            : KnowledgeSufficiency::AwaitsMerchant;
    }

    /**
     * Alguma das lacunas abertas é de texto?
     *
     * Reaproveita `ListingGap::podeSerPreenchidaPelaSugestao()` em vez de
     * repetir a lista: o enum já sabe que só resumo e descrição se resolvem
     * escrevendo, e duplicar esse conhecimento aqui criaria a segunda fonte que
     * o resto desta classe existe para evitar.
     *
     * `tryFrom()` descarta string desconhecida em silêncio, do mesmo modo que a
     * CAT-05E faz ao montar `missing_information` — uma lacuna nova que ninguém
     * mapeou não deve derrubar a política, e o `match` do enum já falha em
     * tempo de compilação quando a sexta chegar.
     *
     * @param  array<int, string>  $lacunas
     */
    private function alguemDeForaPodeEscrever(array $lacunas): bool
    {
        foreach ($lacunas as $lacuna) {
            if (ListingGap::tryFrom($lacuna)?->podeSerPreenchidaPelaSugestao()) {
                return true;
            }
        }

        return false;
    }

    /** A partir de quantas lacunas o conhecimento interno deixa de bastar. */
    private function limiar(): int
    {
        return (int) config('catalog-intelligence.fallback.minimum_gaps', self::LIMIAR_PADRAO);
    }
}
