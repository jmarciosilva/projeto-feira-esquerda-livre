<?php

namespace App\Exceptions;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Alguém tentou reescrever a identidade de um item sem autoridade para isso.
 *
 * A recusa é de **domínio**, não de sessão: quem chega aqui está autenticado,
 * é dono da própria oferta e pode continuar mexendo em preço, estoque, prazo e
 * status dela. O que ele não pode é alterar o que o item *é* — nome, descrições,
 * eixo, categoria, natureza digital —, porque essa verdade é do catálogo e pode
 * estar sendo exibida por outras lojas (D-CAT-09).
 *
 * A mensagem fala de autoridade e não de delegação, coluna ou curadoria: o
 * lojista precisa entender que o pedido foi recusado e a quem recorrer, não
 * como a governança do catálogo está modelada por dentro.
 */
class SemAutoridadeCanonica extends RuntimeException
{
    /** @param  array<int, string>  $campos */
    public function __construct(public readonly array $campos = [])
    {
        parent::__construct($this->mensagemParaOLojista());
    }

    public function mensagemParaOLojista(): string
    {
        return 'Você não possui autoridade para alterar os dados canônicos deste produto. '
            .'Preço, estoque e as demais condições da sua oferta continuam sob seu controle.';
    }

    /**
     * Recusa de autoridade é 403 — mesmo se algum caminho futuro deixar de
     * tratá-la explicitamente, ela nunca vira 500.
     */
    public function render(Request $request): ?JsonResponse
    {
        if (! $request->expectsJson()) {
            return null;
        }

        return response()->json(['message' => $this->mensagemParaOLojista()], 403);
    }
}
