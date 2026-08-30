<?php

namespace App\Exceptions;

use App\Models\OrderSplit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Não se confirma o repasse de uma venda que ainda não foi paga.
 *
 * ## O buraco que esta guarda fecha (G-1)
 *
 * `OrderSplit::confirmar()` olhava só para o próprio split. Nenhuma das duas
 * superfícies que o chamam — o botão do painel do lojista e a rota
 * `PATCH /pedidos/{split}/confirmar-pagamento` — perguntava em que estado
 * estava o **pedido**. Confirmar ali tornava devido o repasse de uma venda sem
 * dinheiro e disparava `OrderSplitConfirmed`, que matricula o aluno: acesso a
 * curso pago, sem pagamento, a um clique.
 *
 * ## Por que a pergunta é sobre pagamento, e não sobre encerramento
 *
 * A primeira versão desta guarda, na FIN-SEC-01G, usava
 * `OrderStatus::ehTerminal()`. Fechava `Cancelado`, `Expirado` e `Estornado` —
 * e deixava `AguardandoPagamento` aberto, que é justamente o caso mais comum:
 * um pedido que simplesmente ainda não foi pago. "Não encerrado" nunca quis
 * dizer "pago".
 *
 * A regra correta é a autoridade financeira, e ela mora em
 * `OrderStatus::temPagamentoConfirmado()`: só `PagamentoConfirmado` e
 * `Concluido` sustentam um repasse devido.
 *
 * ## E quem confirma, então?
 *
 * `ConfirmOrderPayment`, na mesma transação em que o pedido passa a pago — ela
 * confirma **todos** os splits pendentes. Depois dela não sobra split pendente
 * para ninguém confirmar à mão, e é por isso que esta guarda não tira função de
 * ninguém: ela fecha um caminho que só produzia estado inválido.
 */
class SplitDePedidoNaoPago extends RuntimeException
{
    public function __construct(public readonly OrderSplit $split)
    {
        parent::__construct($this->mensagem());
    }

    /**
     * A mensagem nomeia o estado real do pedido.
     *
     * Dizer "encerrado" para um pedido `Aguardando Pagamento` seria mentir para
     * o lojista sobre o motivo da recusa — e o motivo é acionável: o que falta
     * é o pagamento entrar.
     */
    public function mensagem(): string
    {
        return sprintf(
            'O pagamento do pedido ainda não foi confirmado (%s). O repasse só pode ser confirmado depois disso.',
            $this->split->order?->status->label() ?? 'estado desconhecido',
        );
    }

    public function render(Request $request): ?JsonResponse
    {
        if (! $request->expectsJson()) {
            return null;
        }

        return response()->json(['message' => $this->mensagem()], 409);
    }
}
