<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Janela de reserva do checkout
    |--------------------------------------------------------------------------
    |
    | Quanto tempo um pedido recém-criado pode segurar o estoque reservado
    | enquanto ainda não existe intenção de pagamento no gateway.
    |
    | É uma regra interna da plataforma, e não um prazo do Mercado Pago — por
    | isso mora aqui e não em `payment_expires_at`, que significa exclusivamente
    | o prazo objetivo de uma intenção de pagamento conhecida. Assim que uma
    | intenção válida nasce, o prazo dela passa a mandar.
    |
    | Trinta minutos é conservador de propósito: cobre com folga um cliente que
    | vai buscar o cartão ou abre o app do banco, e ainda assim não deixa a
    | última peça presa por horas porque alguém fechou a aba.
    |
    */

    'checkout_reservation_minutes' => (int) env('CHECKOUT_RESERVATION_MINUTES', 30),

];
