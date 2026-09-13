<?php

namespace App\CatalogIntelligence\Enums;

/**
 * Em que condição uma sugestão foi produzida — o desfecho do F-1.
 *
 * CAT-06G (D-CAT-06G-3, D-CAT-06G-11), evoluindo a D-CAT-06B-1. A CAT-06B decidiu
 * quatro estados mínimos, e um deles não era falha. Compor o fluxo de verdade
 * mostrou resultados que nenhum dos quatro descreve — a base bastou, o motor
 * interno caiu, a resposta externa foi aproveitada, o provider respondeu bem e nada
 * tinha onde entrar —, e um desfecho que não cobre todo resultado obriga quem
 * consome a adivinhar pelo resto do objeto.
 *
 * ## Exaustivo, e só por aqui
 *
 * Cada execução do assistente termina em **exatamente um** caso. Quem consome
 * não deduz o desfecho de `source`, de `missing_information`, de sugestão vazia
 * nem de exceção: nenhum desses é o estado, e cada um mente em pelo menos uma
 * situação — a sugestão vazia é normal quando a base não conhece o item e é
 * avaria quando o motor caiu.
 *
 * ## Os quatro da CAT-06B continuam aqui, com o mesmo significado
 *
 * | CAT-06B | Caso |
 * |---|---|
 * | provider ausente | `ProviderUnavailable` |
 * | base não conhece | `InternalKnowledgeInsufficient` |
 * | provider falhou | `ProviderFailed` |
 * | resposta inválida | `ProviderResponseInvalid` |
 *
 * `InternalIntelligenceFailed` **não** é um `ProviderFailed` ampliado: quem caiu
 * foi a base da Feira, e juntar as duas coisas apagaria de qual lado está o
 * defeito.
 *
 * Pelo mesmo motivo `ExternalSuggestionNotUsed` não é um
 * `InternalKnowledgeInsufficient`: um diz que a base não bastou e nada foi pedido
 * fora; o outro, que o provider foi consultado, respondeu bem, e nada da resposta
 * foi aproveitado.
 *
 * ## Descreve a condição, nunca o fornecedor
 *
 * Nenhum caso nomeia provider, modelo ou endpoint (D-CAT-06B-1, nota de
 * fronteira).
 */
enum ListingOutcomeState: string
{
    /** A política julgou o material suficiente; nada externo foi consultado. */
    case InternalKnowledgeSufficient = 'internal_knowledge_sufficient';

    /**
     * O material não bastou, e o que falta é fato que só o lojista tem.
     *
     * A política não justificou consulta (`AwaitsMerchant`): perguntar fora seria
     * pagar por invenção. A sugestão é só interna, o que falta está em
     * `missing_information`, e não é falha.
     */
    case InternalKnowledgeInsufficient = 'internal_knowledge_insufficient';

    /**
     * O motor interno falhou ao buscar conhecimento. Transitório.
     *
     * O provider nem é consultado: a lacuna de conhecimento que a política veria
     * seria produto da falha, e não um fato do item (D-CAT-06G-4). Falha só da
     * similaridade continua acessória e não chega aqui (D-CAT-05F-2).
     */
    case InternalIntelligenceFailed = 'internal_intelligence_failed';

    /** A consulta se justificava, e não há provider disponível. Estado normal (D-CAT-06B-5). */
    case ProviderUnavailable = 'provider_unavailable';

    /** O provider foi tentado e falhou — inclusive por prazo esgotado (B-5). Transitório. */
    case ProviderFailed = 'provider_failed';

    /** O provider respondeu fora do contrato; os motivos vão no desfecho. Não se resolve repetindo. */
    case ProviderResponseInvalid = 'provider_response_invalid';

    /** Algo da resposta externa foi efetivamente incorporado à sugestão. */
    case ExternalSuggestionUsed = 'external_suggestion_used';

    /**
     * O provider foi consultado e respondeu bem, mas nada da resposta entrou.
     *
     * Tudo o que ele propôs já estava escrito pelo lojista, composto pela base ou
     * era equivalente ao que existia — o nome inclusive (D-CAT-06G-12). A sugestão é
     * a interna, intacta. Não é falha, e repetir traria a mesma resposta.
     */
    case ExternalSuggestionNotUsed = 'external_suggestion_not_used';

    /** É avaria? Operar sem IA externa, e a base não conhecer o item, não são. */
    public function ehFalha(): bool
    {
        return match ($this) {
            self::InternalIntelligenceFailed,
            self::ProviderFailed,
            self::ProviderResponseInvalid => true,
            self::InternalKnowledgeSufficient,
            self::InternalKnowledgeInsufficient,
            self::ProviderUnavailable,
            self::ExternalSuggestionUsed,
            self::ExternalSuggestionNotUsed => false,
        };
    }

    /**
     * Faz sentido convidar o lojista a pedir de novo?
     *
     * Só para falha transitória. Resposta inválida é de contrato e se repetiria
     * igual — convidar seria gastar outra consulta pelo mesmo resultado.
     *
     * Não é nova tentativa automática: o assistente tenta uma vez só
     * (D-CAT-06G-6). É a orientação para quem mostra o desfecho.
     */
    public function convidaARepetir(): bool
    {
        return match ($this) {
            self::InternalIntelligenceFailed,
            self::ProviderFailed => true,
            self::InternalKnowledgeSufficient,
            self::InternalKnowledgeInsufficient,
            self::ProviderUnavailable,
            self::ProviderResponseInvalid,
            self::ExternalSuggestionUsed,
            self::ExternalSuggestionNotUsed => false,
        };
    }
}
