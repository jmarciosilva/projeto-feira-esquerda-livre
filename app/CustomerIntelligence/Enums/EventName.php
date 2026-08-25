<?php

namespace App\CustomerIntelligence\Enums;

/**
 * Eventos comportamentais reconhecidos pelo modulo interno.
 *
 * Os sete casos abaixo sao exatamente os que o projeto ja rastreia hoje pelo
 * SDK externo. Eles estao aqui para tipar a fundacao — as chamadas existentes
 * continuam intactas e NAO foram migradas nesta fase (isso e a CI-06).
 *
 * O formato `entidade.acao` e o mesmo do SDK, de proposito: preserva o
 * historico ja coletado e mantem a migracao da CI-06 mecanica.
 */
enum EventName: string
{
    case ProdutoVisualizado = 'produto.visualizado';
    case ProdutoAdicionadoCarrinho = 'produto.adicionado_carrinho';
    case ProdutoRemovidoCarrinho = 'produto.removido_carrinho';
    case CarrinhoCheckoutIniciado = 'carrinho.checkout_iniciado';
    case PedidoCriado = 'pedido.criado';
    case PedidoPagamentoConfirmado = 'pedido.pagamento_confirmado';
    case PedidoEnviado = 'pedido.enviado';

    /**
     * Prefixo do evento, gravado em `ci_events.event_category` para permitir
     * agrupar por familia sem precisar de LIKE no nome.
     */
    public function category(): string
    {
        return explode('.', $this->value)[0];
    }

    public function label(): string
    {
        return match ($this) {
            self::ProdutoVisualizado => 'Produto visualizado',
            self::ProdutoAdicionadoCarrinho => 'Produto adicionado ao carrinho',
            self::ProdutoRemovidoCarrinho => 'Produto removido do carrinho',
            self::CarrinhoCheckoutIniciado => 'Checkout iniciado',
            self::PedidoCriado => 'Pedido criado',
            self::PedidoPagamentoConfirmado => 'Pagamento confirmado',
            self::PedidoEnviado => 'Pedido enviado',
        };
    }
}
