<?php

namespace App\Enums;

/**
 * O que este vendedor tem a receber por esta venda.
 *
 * - **Pendente** — a venda existe, o pagamento ainda não foi confirmado.
 * - **Confirmado** — o pagamento entrou; o repasse é devido.
 * - **Revertido** — o pagamento que sustentava este repasse foi desfeito.
 *
 * `Revertido` é terminal. Um split revertido que voltasse a `Confirmado`
 * redispararia `OrderSplitConfirmed` — rematriculando aluno e reemitindo
 * evento —, que é precisamente o que a guarda de `OrderSplit::confirmar()`
 * impede desde a FIN-SEC-01F-B.
 */
enum OrderSplitStatus: string
{
    case Pendente   = 'pendente';
    case Confirmado = 'confirmado';
    case Revertido  = 'revertido';

    public function label(): string
    {
        return match($this) {
            self::Pendente   => 'Aguardando confirmação',
            self::Confirmado => 'Pagamento confirmado',
            self::Revertido  => 'Pagamento revertido',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Pendente   => 'yellow',
            self::Confirmado => 'green',
            self::Revertido  => 'red',
        };
    }

    /**
     * Classes de badge para as views, que antes usavam `else = Pendente`.
     *
     * Com dois casos, tratar "o que não é confirmado" como pendente funcionava
     * por acidente. Com três, um split revertido apareceria amarelo, como se
     * ainda houvesse o que receber.
     */
    public function badge(): string
    {
        return match($this) {
            self::Pendente   => 'background:#fef9c3; color:#854d0e;',
            self::Confirmado => 'background:#dcfce7; color:#166534;',
            self::Revertido  => 'background:#fee2e2; color:#991b1b;',
        };
    }
}
