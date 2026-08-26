<?php

namespace App\CustomerIntelligence\Enums;

/**
 * Eventos comportamentais reconhecidos pelo modulo interno.
 *
 * Sao os sete eventos de negocio que a plataforma rastreia. Tipa-los evita a
 * classe inteira de erro de digitacao silenciosa que strings soltas permitem.
 *
 * O formato `entidade.acao` e historico e foi preservado para nao quebrar a
 * continuidade dos dados ja coletados.
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
