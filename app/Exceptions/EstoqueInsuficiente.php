<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Não há unidades suficientes para comprometer o que este pedido pede.
 *
 * Exceção de domínio, e não de infraestrutura: ela existe para que o checkout
 * possa dizer ao cliente "restou 1 unidade" em vez de estourar um erro técnico,
 * e para que a confirmação de pagamento falhe fechada em vez de deixar estoque
 * negativo.
 */
class EstoqueInsuficiente extends RuntimeException
{
    /**
     * @param  string  $item  nome do item, como o cliente o vê
     * @param  int  $pedido  quantidade solicitada
     * @param  int  $disponivel  quantidade que sobrou
     */
    public function __construct(
        public readonly string $item,
        public readonly int $pedido,
        public readonly int $disponivel,
    ) {
        parent::__construct($this->mensagemParaOCliente());
    }

    /**
     * Texto que pode ir para a tela sem revelar nada de dentro do sistema.
     */
    public function mensagemParaOCliente(): string
    {
        if ($this->disponivel <= 0) {
            return "\"{$this->item}\" esgotou enquanto você finalizava a compra.";
        }

        return sprintf(
            '"%s" tem apenas %d %s em estoque, e você pediu %d.',
            $this->item,
            $this->disponivel,
            $this->disponivel === 1 ? 'unidade' : 'unidades',
            $this->pedido,
        );
    }
}
