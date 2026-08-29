<?php

namespace App\Exceptions;

use App\Models\OrderSplit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * O repasse deste vendedor foi desfeito e não volta por clique.
 *
 * `Revertido` é terminal. Reconfirmar dispararia `OrderSplitConfirmed` de novo,
 * e cada disparo carrega efeito de negócio — matrícula em curso, evento de
 * inteligência de clientes. A guarda vive no domínio, e não no botão, porque
 * são duas as superfícies que confirmam: o painel do lojista e a rota
 * `PATCH /pedidos/{split}/confirmar-pagamento`.
 */
class SplitRevertidoNaoReconfirma extends RuntimeException
{
    public function __construct(public readonly OrderSplit $split)
    {
        parent::__construct($this->mensagem());
    }

    public function mensagem(): string
    {
        return 'O pagamento desta venda foi revertido e não pode ser confirmado novamente.';
    }

    public function render(Request $request): ?JsonResponse
    {
        if (! $request->expectsJson()) {
            return null;
        }

        return response()->json(['message' => $this->mensagem()], 409);
    }
}
