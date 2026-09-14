<?php

namespace App\CatalogIntelligence\Exceptions;

use RuntimeException;

/**
 * A falha **esperada** do outro lado da fronteira do provider.
 *
 * CAT-06G (D-CAT-06G-7). É a única exceção que o assistente trata como "o
 * provider falhou": `GenerateListingSuggestion` a captura em volta de
 * `isAvailable()` e de `suggest()` e devolve a sugestão interna com o desfecho
 * `ProviderFailed`. Nenhuma outra é capturada ali.
 *
 * ## Por que tipada, e não `Throwable`
 *
 * Capturar tudo transformaria `TypeError`, erro de programação e regressão
 * interna em "provider fora do ar" — um defeito permanente anunciado como falha
 * transitória, que convida o lojista a repetir o que nunca vai funcionar. Com a
 * exceção tipada a fronteira vira contrato: o adaptador converte nesta classe o
 * que **espera** que dê errado no transporte (tempo esgotado, recusa, resposta
 * ilegível); o que ele não espera continua subindo, e aparece.
 *
 * ## Uma classe, sem hierarquia
 *
 * Todo motivo de falha externa leva ao mesmo desfecho, então não há o que uma
 * subclasse distinguiria para o assistente. `tempoEsgotado()` existe porque o
 * tempo é a única falha com política própria (B-5), e não para ser tratado de
 * outro jeito.
 *
 * ## A mensagem não é registrada
 *
 * Quem escreve a mensagem é o adaptador, e a de uma falha de transporte pode
 * carregar trecho do prompt ou da resposta. O assistente registra a classe e a
 * etapa, nunca `getMessage()`.
 */
final class CatalogAiProviderException extends RuntimeException
{
    /**
     * O prazo da tentativa externa se esgotou — B-5.
     *
     * A política (D-CAT-06G-5, D-CAT-06G-6) é **8 segundos no total**, aplicados
     * pelo adaptador no transporte, e **nenhuma** nova tentativa. Desde a CAT-10A o
     * valor vem de config, lido fora do módulo, e nunca passa de 8 segundos.
     */
    public static function tempoEsgotado(): self
    {
        return new self('o provider não respondeu dentro do prazo da tentativa');
    }
}
