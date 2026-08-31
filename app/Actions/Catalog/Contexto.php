<?php

namespace App\Actions\Catalog;

/**
 * Para que se está resolvendo a oferta (CAT-DOM-02G).
 *
 * Vive ao lado de `ResolveProductOffer` porque só faz sentido junto dele — não
 * é um conceito do domínio de catálogo, é o parâmetro de uma decisão.
 */
enum Contexto
{
    /**
     * Vai virar carrinho, pedido ou cotação: exige oferta **vigente**.
     *
     * Não se compra de loja fechada nem item que o lojista recolheu, e
     * `ProductOffer::isVigente()` já reúne as três condições que respondem
     * isso — oferta ativa, expositor ativo, produto ativo.
     */
    case Compra;

    /**
     * Vai olhar para trás: aceita oferta **inativa**.
     *
     * Pedido, matrícula e comprovante apontam para o que foi vendido, não para
     * o que ainda está à venda. Resolver histórico pelo estado atual faria a
     * oferta desativada ontem trocar de dono hoje — é o erro que a D-02G-4
     * proíbe, e a mesma razão pela qual a FIN-SEC guarda snapshot em vez de
     * reler o catálogo.
     */
    case Historico;
}
