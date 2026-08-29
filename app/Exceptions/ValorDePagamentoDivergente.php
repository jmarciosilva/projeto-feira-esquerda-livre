<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * O que o gateway aprovou não é o que este pedido cobra.
 *
 * Cobre os dois modos de falha da mesma regra: o valor aprovado difere do total
 * do pedido, e o valor aprovado não é legível (ausente, nulo, texto, negativo).
 * Nos dois casos a confirmação é recusada — a diferença entre eles interessa
 * para o log, não para a decisão.
 *
 * ## Por que uma classe própria
 *
 * A recusa já existia desde a FIN-SEC-01D, mas como `RuntimeException` genérica.
 * A partir da 01F-D quem captura precisa **classificar** o que aconteceu, para
 * abrir o `PaymentConflict` do tipo certo: `amount_mismatch` pede um caminho de
 * reconciliação diferente de `insufficient_stock`. Distinguir por mensagem de
 * texto seria frágil; distinguir por tipo é o que o PHP já sabe fazer.
 *
 * Estende `RuntimeException` de propósito: todo `catch (RuntimeException)` que
 * já existia continua capturando esta.
 */
class ValorDePagamentoDivergente extends RuntimeException
{
    /**
     * @param  int|null  $pagoEmCentavos  valor aprovado, quando legível
     * @param  int  $esperadoEmCentavos  total do pedido
     */
    private function __construct(
        public readonly string $referencia,
        public readonly ?int $pagoEmCentavos,
        public readonly int $esperadoEmCentavos,
        string $mensagem,
    ) {
        parent::__construct($mensagem);
    }

    public static function semValorConfiavel(string $referencia, int $esperadoEmCentavos): self
    {
        return new self($referencia, null, $esperadoEmCentavos, sprintf(
            'Pagamento aprovado para o pedido %s sem valor confiável — confirmação recusada.',
            $referencia,
        ));
    }

    public static function naoCorresponde(string $referencia, int $pagoEmCentavos, int $esperadoEmCentavos): self
    {
        return new self($referencia, $pagoEmCentavos, $esperadoEmCentavos, sprintf(
            'Pagamento de %d centavos não corresponde ao pedido %s, de %d centavos.',
            $pagoEmCentavos,
            $referencia,
            $esperadoEmCentavos,
        ));
    }
}
