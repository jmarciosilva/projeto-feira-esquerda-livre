<?php

namespace App\Enums;

/**
 * O estado comercial de um pedido.
 *
 * Os seis casos respondem perguntas diferentes, e confundi-los foi a origem de
 * boa parte da trilha FIN-SEC:
 *
 * - **AguardandoPagamento** — pedido criado e ainda pagável.
 * - **PagamentoConfirmado** — pagamento aprovado e domínio comercial confirmado.
 * - **Cancelado** — intenção de compra encerrada *antes* da confirmação
 *   financeira. Ninguém pagou nada.
 * - **Expirado** — a intenção de pagamento perdeu validade temporal. Também não
 *   houve pagamento, mas o motivo é o relógio, não uma decisão de alguém.
 * - **Estornado** — um pagamento que *estava* confirmado sofreu reversão
 *   financeira. Houve dinheiro, e ele voltou.
 * - **Concluido** — operação comercial e logística encerrada com sucesso.
 *
 * `Cancelado` não é sinônimo de `Estornado`. Até a FIN-SEC-01F-B o gateway
 * escrevia `Cancelado` também para estorno, o que apagava a informação de que
 * o pedido chegou a ser pago.
 */
enum OrderStatus: string
{
    case AguardandoPagamento = 'aguardando_pagamento';
    case PagamentoConfirmado = 'pagamento_confirmado';
    case Concluido           = 'concluido';
    case Cancelado           = 'cancelado';
    case Expirado            = 'expirado';
    case Estornado           = 'estornado';

    public function label(): string
    {
        return match($this) {
            self::AguardandoPagamento => 'Aguardando Pagamento',
            self::PagamentoConfirmado => 'Pagamento Confirmado',
            self::Concluido           => 'Concluído',
            self::Cancelado           => 'Cancelado',
            self::Expirado            => 'Expirado',
            self::Estornado           => 'Estornado',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::AguardandoPagamento => 'yellow',
            self::PagamentoConfirmado => 'blue',
            self::Concluido           => 'green',
            self::Cancelado           => 'red',
            self::Expirado            => 'gray',
            self::Estornado           => 'red',
        };
    }

    /**
     * Estado do qual o pedido não sai mais.
     *
     * Não é adorno: é o que impede um job de logística ou um webhook atrasado
     * de ressuscitar um pedido que já foi encerrado.
     */
    public function ehTerminal(): bool
    {
        return match($this) {
            self::Cancelado, self::Expirado, self::Estornado, self::Concluido => true,
            default => false,
        };
    }

    /**
     * A matriz de transições, declarada num lugar só.
     *
     * Espalhar essa regra por actions e superfícies foi exatamente o que
     * permitiu ao painel admin fabricar qualquer estado a partir de qualquer
     * outro. Quem quiser transicionar pergunta aqui.
     *
     * @return array<int, self>
     */
    public function destinosPermitidos(): array
    {
        return match($this) {
            self::AguardandoPagamento => [self::PagamentoConfirmado, self::Cancelado, self::Expirado],
            self::PagamentoConfirmado => [self::Concluido, self::Estornado],
            self::Concluido, self::Cancelado, self::Expirado, self::Estornado => [],
        };
    }

    public function podeIrPara(self $destino): bool
    {
        return in_array($destino, $this->destinosPermitidos(), true);
    }
}
