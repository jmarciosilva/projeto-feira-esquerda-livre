<?php

namespace App\CatalogIntelligence\DTOs;

use App\CatalogIntelligence\Enums\ProviderInstruction;

/**
 * Instrução, contexto recuperado e dado do lojista, em canais que não se misturam.
 *
 * CAT-06F, gate **S-1** (D-CAT-06F-1). É o que o `PromptGuard` devolve, e a
 * forma que a §5.2 da especificação pediu: *"separação explícita entre
 * instrução do sistema, contexto recuperado e dado do usuário"*.
 *
 * ## A separação é estrutural, não sintática
 *
 * Não há delimitador a escapar porque não há string única onde os três se
 * encontrem. Cada canal é uma propriedade própria, e esta classe não tem método
 * algum além do construtor: nenhum `__toString()`, nenhum "renderizar". Um
 * lojista que escreva um fechamento de tag, um cabeçalho "INSTRUÇÕES" ou um JSON
 * com papel de sistema produz uma string dentro do canal de dado — e ela
 * continua sendo só isso, porque o canal é uma posição na estrutura, e não um
 * trecho de texto.
 *
 * | Canal | Tipo | Origem | Manda? |
 * |---|---|---|---|
 * | `instruction` | `ProviderInstruction` | código da aplicação | **sim** — é o único |
 * | `context` | array | recuperado pela aplicação: conceitos e itens semelhantes | não |
 * | `data` | array | o item que o lojista está cadastrando | não |
 *
 * `context` e `data` são os dois não confiáveis. O primeiro traz descrição
 * curada e nome de item de **outro** lojista; o segundo, o que **este** lojista
 * digitou. Nenhum deles é instrução, e a diferença entre os dois é de origem —
 * que um provider pode querer pesar de modo diferente.
 *
 * ## Quem traduz para o fornecedor
 *
 * O adaptador que implementar `CatalogAiProvider` para um fornecedor. É ele que
 * recebe este objeto em `suggest()` — já com o conteúdo redigido pelo
 * `GuardedPromptRedactor` — e mapeia os três canais para o mecanismo que o
 * fornecedor tiver. O que ele não pode é juntá-los numa string só antes disso.
 * O binding padrão, `NullCatalogAiProvider`, não traduz nada.
 *
 * ## Imutável
 *
 * `final` e `readonly`: quem recebe não troca a instrução nem enxerta dado nela
 * depois de montado. O tipo do canal de instrução faz o resto — texto não cabe
 * nele.
 */
final class GuardedPrompt
{
    /**
     * @param  array<string, mixed>  $context  Contexto recuperado pela aplicação.
     * @param  array<string, mixed>  $data  O item, como o lojista o informou.
     */
    public function __construct(
        public readonly ProviderInstruction $instruction,
        public readonly array $context,
        public readonly array $data,
    ) {}
}
