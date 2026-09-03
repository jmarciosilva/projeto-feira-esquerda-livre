<?php

namespace App\CatalogIntelligence\Contracts;

use App\CatalogIntelligence\DTOs\ListingContext;
use App\CatalogIntelligence\DTOs\ListingSuggestion;

/**
 * A fronteira por onde uma inteligência de fora poderia entrar.
 *
 * CAT-06D. A assinatura é a da §3.3 do documento arquitetural, **transcrita e
 * não redigida de novo** — a CAT-06A §3 já a havia confirmado literalmente, e
 * reescrevê-la aqui abriria espaço para divergir da spec por descuido.
 *
 * ## O domínio não sabe quem está do outro lado
 *
 * Nenhum nome de fornecedor, de modelo ou de endpoint aparece neste arquivo,
 * nas implementações, ou em qualquer lugar do módulo. Não é estilo: é o que
 * permite trocar o que está atrás do contrato sem tocar em uma linha de
 * domínio, e é verificado por teste que varre o módulo inteiro.
 *
 * ## Duas implementações, e nenhuma delas fala com a rede
 *
 * A CAT-06D entrega `NullCatalogAiProvider` — o caminho de produção enquanto
 * não houver credencial (D-CAT-06B-5) — e `FakeCatalogAiProvider`, para teste.
 * **Nenhuma implementação real é escrita nesta trilha**, e ao fim da CAT-06
 * nenhum texto sai da aplicação.
 *
 * ## `isAvailable()` é pergunta, não promessa
 *
 * Responde *"faz sentido tentar?"* — credencial configurada, recurso ligado.
 * Não promete que a chamada seguinte vai dar certo: provider disponível que
 * falha é um estado previsto, e é o terceiro dos quatro do desfecho de F-1
 * (D-CAT-06B-1). Quem chama **deve** perguntar antes de chamar `suggest()`.
 *
 * ## `suggest()` devolve o DTO, e o tipo não basta
 *
 * O retorno é tipado, então o PHP garante a **classe**. Ele não garante o
 * **conteúdo**: `keywords` é `array` e aceita qualquer coisa dentro,
 * `confidence` é `?float` e aceita 42, e um texto em branco passa por
 * `?string`. É essa lacuna que a B-4 fecha, em
 * `Support\ProviderResponseValidator` — ver o docblock de lá.
 */
interface CatalogAiProvider
{
    public function isAvailable(): bool;

    public function suggest(ListingContext $context): ListingSuggestion;
}
