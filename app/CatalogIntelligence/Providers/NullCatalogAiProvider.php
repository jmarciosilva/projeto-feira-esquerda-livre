<?php

namespace App\CatalogIntelligence\Providers;

use App\CatalogIntelligence\Contracts\CatalogAiProvider;
use App\CatalogIntelligence\DTOs\ListingContext;
use App\CatalogIntelligence\DTOs\ListingSuggestion;

/**
 * O caminho de produção enquanto não houver credencial — e ele não é degradado.
 *
 * **D-CAT-06B-5**: operar sem inteligência externa é **estado normal**, não
 * avaria. Esta classe é o que torna isso literal: a aplicação roda inteira com
 * ela no lugar, e nada quebra, porque quem consome já pergunta
 * `isAvailable()` antes de chamar.
 *
 * ## Nunca lança. Nenhuma exceção, em nenhum caminho.
 *
 * É a invariante que a spec §3.3 fixa — *"não lança exceção: devolve
 * indisponibilidade"* — e a razão é a mesma que a CAT-05F usou para a
 * degradação parcial: ausência de conhecimento é o estado normal de um
 * catálogo que está começando, e ausência de **fornecedor** é o estado normal
 * de uma aplicação que não contratou nenhum. Tratar qualquer um dos dois como
 * erro obrigaria cada superfície a envolver a chamada em `try`.
 *
 * ## O que `suggest()` faz se for chamado mesmo assim
 *
 * Devolve `ListingSuggestion::vazia()`.
 *
 * Chamar `suggest()` sem checar `isAvailable()` é erro de quem chama, mas erro
 * de chamador não pode virar exceção em produção. As três alternativas foram
 * consideradas e recusadas:
 *
 * - **lançar** viola a invariante acima, e é o modo de falha mais caro
 *   justamente no caminho mais comum de produção;
 * - **devolver `null`** mudaria o tipo de retorno do contrato e obrigaria todo
 *   consumidor a testar nulo — a mesma armadilha que `vazia()` foi criada para
 *   evitar na CAT-05D;
 * - **devolver uma sugestão com texto** seria inventar conteúdo, que é o que a
 *   trilha inteira existe para não fazer.
 *
 * A vazia carrega `source: Internal`, e isso é verdade: **nada externo
 * contribuiu**. A consequência é que passar esta resposta pelo
 * `ProviderResponseValidator` acusaria procedência incorreta — o que está
 * certo, e não é um caso que a 06G vá produzir, porque ela checa
 * `isAvailable()` antes.
 */
final class NullCatalogAiProvider implements CatalogAiProvider
{
    public function isAvailable(): bool
    {
        return false;
    }

    public function suggest(ListingContext $context): ListingSuggestion
    {
        return ListingSuggestion::vazia();
    }
}
