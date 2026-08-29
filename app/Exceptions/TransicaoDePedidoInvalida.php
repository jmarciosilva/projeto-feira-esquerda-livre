<?php

namespace App\Exceptions;

use App\Enums\OrderStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * O pedido não pode ir daqui para lá.
 *
 * Exceção de domínio: a matriz de `OrderStatus::destinosPermitidos()` recusou a
 * transição. Existe para que a recusa chegue à superfície como negativa
 * comercial — 409 na API, recado na tela — e nunca como erro de servidor.
 */
class TransicaoDePedidoInvalida extends RuntimeException
{
    public function __construct(
        public readonly OrderStatus $origem,
        public readonly OrderStatus $destino,
    ) {
        parent::__construct($this->mensagem());
    }

    public function mensagem(): string
    {
        return sprintf(
            'Um pedido "%s" não pode passar para "%s".',
            $this->origem->label(),
            $this->destino->label(),
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
