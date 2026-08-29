<?php

namespace App\Events;

use App\Models\OrderSplit;

/**
 * O repasse deste vendedor deixou de ser devido: o pagamento que o sustentava
 * foi desfeito.
 *
 * É o espelho de `OrderSplitConfirmed`, e existe pelo mesmo motivo — o efeito
 * de negócio não pertence a quem transiciona. `ReverseOrderPayment` sabe reverter
 * um pedido; ela não precisa saber que existe um AVA do outro lado, nem que um
 * dia possa existir um repasse a cancelar ou um e-mail a enviar.
 *
 * Como o confirmado, sai **depois do commit**: um listener que revogue acesso
 * sobre uma reversão que ainda pode sofrer rollback deixaria aluno trancado
 * fora de um curso que continua pago.
 */
class OrderSplitReverted
{
    public function __construct(
        public readonly OrderSplit $split,
    ) {}
}
