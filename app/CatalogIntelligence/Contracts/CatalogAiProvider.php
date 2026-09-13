<?php

namespace App\CatalogIntelligence\Contracts;

use App\CatalogIntelligence\DTOs\GuardedPrompt;
use App\CatalogIntelligence\DTOs\ListingSuggestion;
use App\CatalogIntelligence\Exceptions\CatalogAiProviderException;

/**
 * A fronteira por onde uma inteligência de fora poderia entrar.
 *
 * CAT-06D, com a assinatura de `suggest()` evoluída na CAT-06G (D-CAT-06G-1).
 *
 * ## O que atravessa a fronteira é o prompt protegido
 *
 * A CAT-06D transcreveu a assinatura da §3.3 da especificação,
 * `suggest(ListingContext)`, e ela bastava enquanto o redator e o guard existiam
 * isolados. Ao compô-los, a CAT-06G mostrou que `ListingContext` não é uma
 * fronteira segura de transporte: ele carrega o texto como o lojista escreveu,
 * sem canais e sem redação, e nada no tipo obrigava quem implementasse o contrato
 * a proteger o conteúdo antes de enviá-lo.
 *
 * Recebendo `GuardedPrompt`, o provider só recebe o que já passou pelo
 * `PromptGuard` (S-1) e pelo `GuardedPromptRedactor` (C-2). Quem monta esse prompt
 * é `GenerateListingSuggestion`; o adaptador traduz os três canais para o
 * mecanismo do fornecedor, sem juntá-los numa string antes disso.
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
 * não houver credencial (D-CAT-06B-5), registrado como padrão na CAT-06G — e
 * `FakeCatalogAiProvider`, para teste. **Nenhuma implementação real é escrita
 * nesta trilha**, e ao fim da CAT-06 nenhum texto sai da aplicação.
 *
 * ## `isAvailable()` é pergunta, não promessa
 *
 * Responde *"faz sentido tentar?"* — credencial configurada, recurso ligado.
 * Não promete que a chamada seguinte vai dar certo: provider disponível que
 * falha é um estado previsto, o desfecho `ProviderFailed` (D-CAT-06G-3). Quem
 * chama **deve** perguntar antes de chamar `suggest()`.
 *
 * ## Falha esperada é `CatalogAiProviderException`, e só ela
 *
 * Os dois métodos podem lançar `CatalogAiProviderException` quando algo
 * **previsto** der errado do outro lado — prazo esgotado, recusa, resposta que
 * não se consegue ler. O adaptador converte essas falhas do transporte nessa
 * exceção, e o assistente a transforma no desfecho `ProviderFailed`, com a
 * sugestão interna preservada.
 *
 * Qualquer outra exceção ou erro **não é** falha de provider: é defeito, e sobe
 * (D-CAT-06G-7).
 *
 * ## Prazo e novas tentativas (B-5)
 *
 * A tentativa externa tem **8 segundos no total**, aplicados pelo adaptador no
 * transporte; esgotado o prazo, ele lança
 * `CatalogAiProviderException::tempoEsgotado()`. **Não há nova tentativa** — nem
 * no adaptador, nem no assistente (D-CAT-06G-5, D-CAT-06G-6).
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
    /** @throws CatalogAiProviderException */
    public function isAvailable(): bool;

    /** @throws CatalogAiProviderException */
    public function suggest(GuardedPrompt $prompt): ListingSuggestion;
}
