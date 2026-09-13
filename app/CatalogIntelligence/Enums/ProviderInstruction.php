<?php

namespace App\CatalogIntelligence\Enums;

/**
 * A instrução que a aplicação dá a um provider — o canal de autoridade do S-1.
 *
 * CAT-06F (D-CAT-06F-2). É o único canal de `GuardedPrompt` que manda, e por
 * isso o único que o lojista não alcança.
 *
 * ## Enum puro, e não string
 *
 * Uma instrução em `string` aceitaria qualquer texto — inclusive o nome de um
 * item. O enum fecha o conjunto no código: não existe valor digitado em
 * formulário que vire um caso deste tipo.
 *
 * Por isso ele **não tem valor de apoio**. Com `: string`, `from()` e
 * `tryFrom()` existiriam, e bastaria um `tryFrom()` sobre entrada de usuário em
 * algum lugar para texto de fora escolher a instrução. Sem valor de apoio, esses
 * métodos nem existem.
 *
 * ## Identifica a instrução; não a escreve
 *
 * O texto da instrução é o prompt, e o prompt não existe nesta fase: nada sai da
 * aplicação ao fim da CAT-06. Quem traduz o caso para o formato de um fornecedor
 * é o adaptador que a CAT-06G vier a ligar — e se o texto das regras mora no
 * domínio ou no adaptador é decisão dela.
 */
enum ProviderInstruction
{
    /** Propor resumo, descrição e palavras-chave para o item — o `suggest()` do contrato. */
    case SuggestListing;
}
