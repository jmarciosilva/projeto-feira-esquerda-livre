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
     *
     * ## Derivado, e não declarado
     *
     * A resposta é lida da própria matriz. Enquanto as duas listas eram escritas
     * à mão, elas podiam discordar — e discordaram: a FIN-SEC-01F-D abriu
     * `Concluido → Estornado` e deixaria `ehTerminal()` afirmando que um pedido
     * concluído não vai a lugar nenhum, o que passou a ser falso.
     *
     * `Concluido` deixa de ser terminal por um motivo concreto, e não por
     * elegância: uma compra entregue pode sofrer estorno depois. Ver a nota de
     * `destinosPermitidos()`.
     */
    public function ehTerminal(): bool
    {
        return $this->destinosPermitidos() === [];
    }

    /**
     * A matriz de transições, declarada num lugar só.
     *
     * Espalhar essa regra por actions e superfícies foi exatamente o que
     * permitiu ao painel admin fabricar qualquer estado a partir de qualquer
     * outro. Quem quiser transicionar pergunta aqui.
     *
     * ## Por que `Concluido → Estornado` existe (FIN-SEC-01F-D)
     *
     * Porque a realidade tem esse caminho: pago, enviado, entregue, concluído —
     * e estornado um mês depois. Recusar a transição não impediria o estorno de
     * acontecer no mundo; só impediria a plataforma de registrá-lo, deixando um
     * pedido `Concluido` cujo dinheiro já voltou e cujo vendedor continua
     * figurando como tendo a receber.
     *
     * ## E por que isso não mistura logística com financeiro
     *
     * A pergunta óbvia é se `orders.status` não estaria representando duas
     * dimensões ao mesmo tempo — conclusão logística e situação financeira. A
     * resposta é não, e o motivo é que **a dimensão logística já mora em outro
     * lugar**: `order_shippings.status`, `order_shippings.delivered_at` e
     * `order_tracking_events` guardam o que foi enviado, por quem, e quando
     * chegou. `Concluido` é uma projeção disso — `TrackShipmentsJob` só o
     * escreve quando todos os envios estão `Delivered`, e é o único escritor.
     *
     * Estornar um pedido entregue não apaga nada disso: os envios continuam
     * `Delivered`, `delivered_at` continua preenchido, `paid_at` continua
     * dizendo que houve pagamento, e `reversed_at` acrescenta que ele voltou.
     * Relatório, logística e atendimento seguem respondíveis. Por isso a fase
     * não precisou criar um `financial_status` separado: separar já estava
     * separado, só não estava dito.
     *
     * @return array<int, self>
     */
    public function destinosPermitidos(): array
    {
        return match($this) {
            self::AguardandoPagamento => [self::PagamentoConfirmado, self::Cancelado, self::Expirado],
            self::PagamentoConfirmado => [self::Concluido, self::Estornado],
            self::Concluido => [self::Estornado],
            self::Cancelado, self::Expirado, self::Estornado => [],
        };
    }

    public function podeIrPara(self $destino): bool
    {
        return in_array($destino, $this->destinosPermitidos(), true);
    }
}
