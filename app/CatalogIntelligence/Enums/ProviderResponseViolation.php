<?php

namespace App\CatalogIntelligence\Enums;

/**
 * O que pode estar errado numa resposta de provider que o tipo já aceitou.
 *
 * CAT-06D, dívida **B-4**. Cada caso é uma coisa que `ListingSuggestion` aceita
 * no construtor e que **não deveria** atravessar a fronteira — o conjunto é
 * fechado e vem do que o PHP não consegue garantir, não de imaginação.
 *
 * É enum, e não string solta, pelo mesmo motivo de `ListingGap`: um `match`
 * sobre enum falha em tempo de compilação quando alguém acrescentar o sexto
 * caso, e a 06G precisa poder decidir por identidade — não por comparar texto.
 */
enum ProviderResponseViolation: string
{
    /**
     * Texto que existe como string mas não diz nada: `''` ou só espaço.
     *
     * `?string` aceita os dois, e `temAlgoAPropor()` responde **true** para
     * eles — a CAT-09 ofereceria ao lojista um campo em branco como se fosse
     * proposta. Nulo é a forma correta de dizer "não tenho o que propor".
     */
    case TextoEmBranco = 'texto_em_branco';

    /**
     * `keywords` não é lista de strings não vazias.
     *
     * A propriedade é `array` e o `@param array<int, string>` do DTO é
     * documentação, não garantia: array associativo, aninhado, com inteiros ou
     * com string vazia passa pelo construtor sem reclamar.
     */
    case KeywordsMalformadas = 'keywords_malformadas';

    /** `missingInformation` não é lista de strings não vazias — mesmo caso. */
    case MissingInformationMalformada = 'missing_information_malformada';

    /**
     * `confidence` fora de `[0, 1]`.
     *
     * `?float` aceita −3 e 42. Um número fora da faixa exibido a um lojista
     * como confiança é pior que campo nulo, e a CAT-05D já decidiu que nulo é
     * resposta legítima aqui.
     */
    case ConfiancaForaDeFaixa = 'confianca_fora_de_faixa';

    /**
     * A resposta não se declara `External`.
     *
     * `SuggestionSource` é enum e o tipo garante que seja um dos dois — mas não
     * garante o **certo**. Uma resposta de provider rotulada `Internal` mente
     * sobre a procedência, e é a CAT-07 que vai registrar isso como histórico:
     * procedência errada não é cosmética, corrompe a auditoria.
     */
    case ProcedenciaIncorreta = 'procedencia_incorreta';
}
