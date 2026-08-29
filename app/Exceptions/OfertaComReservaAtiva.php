<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * A oferta ainda deve unidades a pedidos que existem.
 *
 * Enquanto houver reserva ativa, a `ProductOffer` não é só um registro de
 * catálogo: é o recurso operacional de que `ConsumeOrderStock` e
 * `ReleaseOrderStock` precisam para baixar ou devolver aquelas unidades. Apagar
 * a linha deixaria o pedido reservado apontando para o nada — e a obrigação não
 * pode ser assumida em silêncio por outra oferta do mesmo produto, que é de
 * outro vendedor e de outro preço.
 *
 * Desativar continua liberado: preserva a linha, e com ela a reserva.
 */
class OfertaComReservaAtiva extends RuntimeException
{
    public function __construct(
        public readonly string $item,
        public readonly int $reservado,
    ) {
        parent::__construct($this->mensagemParaOLojista());
    }

    public function mensagemParaOLojista(): string
    {
        return sprintf(
            '"%s" tem %d %s reservada%s por pedidos pendentes e não pode ser excluída agora. Você pode desativá-la: ela sai da vitrine e os pedidos em aberto continuam válidos.',
            $this->item,
            $this->reservado,
            $this->reservado === 1 ? 'unidade' : 'unidades',
            $this->reservado === 1 ? '' : 's',
        );
    }

    /**
     * Recusa comercial é 409, nunca 500 — mesmo se algum caminho futuro deixar
     * de tratá-la explicitamente.
     */
    public function render(Request $request): ?JsonResponse
    {
        if (! $request->expectsJson()) {
            return null;
        }

        return response()->json(['message' => $this->mensagemParaOLojista()], 409);
    }
}
