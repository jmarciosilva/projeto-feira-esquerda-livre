<?php

namespace App\CatalogIntelligence\Enums;

/**
 * O veredito da `SuggestionPolicy`: há material suficiente para sugerir?
 *
 * ## Por que não um booleano
 *
 * A pergunta óbvia — *"o conhecimento interno basta?"* — parece binária, e a
 * CAT-06B chegou a formulá-la assim. Ela não é, e o motivo está no enum
 * `ListingGap`, que já existia antes desta subfase.
 *
 * `ListingGap::podeSerPreenchidaPelaSugestao()` diz que apenas duas das cinco
 * lacunas — resumo e descrição — podem ser preenchidas por quem escreve texto.
 * As outras três dependem de uma pessoa: **categoria** é escolha do lojista,
 * **atributo** é fato que só ele sabe (material, medidas, origem) e
 * **conhecimento** é trabalho da curadoria.
 *
 * Então "insuficiente" esconde dois casos que pedem ações opostas:
 *
 * - falta texto, e texto é exatamente o que uma inteligência externa produz;
 * - falta fato, e fato nenhuma inteligência produz — ela **inventa**.
 *
 * Consultar um modelo externo sobre o material de uma peça é o caminho mais
 * curto para uma medida alucinada entrar no catálogo. A regra 1 das invioláveis
 * manda o contrário: *"na dúvida: omitir e pedir a informação ao lojista"*, e a
 * CAT-05E construiu `missing_information` inteira sobre esse princípio.
 *
 * Um booleano forçaria a CAT-06G a redescobrir a distinção no momento de gastar
 * dinheiro com a consulta — que é o pior momento para descobri-la.
 *
 * ## O que este enum **não** decide
 *
 * Não decide consultar. Decide se **valeria a pena** consultar. Quem consulta é
 * a CAT-06G, depois que o redator (06E) e o guard (06F) existirem — ordem
 * travada em D-CAT-06B-6.
 */
enum KnowledgeSufficiency: string
{
    /**
     * Há material suficiente. O assistente interno resolve sozinho.
     *
     * É o caminho normal e o mais barato: nenhuma consulta externa se justifica
     * quando a base já conhece o item.
     */
    case Sufficient = 'sufficient';

    /**
     * Falta material, e o que falta é **texto** — consulta externa ajudaria.
     *
     * É o único veredito que autoriza a CAT-06G a considerar o fallback.
     */
    case ExternalMayHelp = 'external_may_help';

    /**
     * Falta material, mas o que falta **só o lojista tem**.
     *
     * Categoria, atributo e conhecimento curado não se resolvem com inteligência
     * nenhuma. Consultar aqui gastaria dinheiro para receber invenção, então o
     * caminho certo é `missing_information` — pedir, não adivinhar.
     */
    case AwaitsMerchant = 'awaits_merchant';

    /**
     * A CAT-06G deve considerar consultar algo externo?
     *
     * Existe para que o chamador não precise repetir a comparação e, no dia em
     * que houver um quarto caso, não haja um `=== ExternalMayHelp` solto em
     * outro arquivo para achar.
     */
    public function justificaConsultaExterna(): bool
    {
        return $this === self::ExternalMayHelp;
    }
}
