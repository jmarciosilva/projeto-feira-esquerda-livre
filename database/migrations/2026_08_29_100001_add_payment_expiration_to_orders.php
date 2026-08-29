<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FIN-SEC-01F-C — até quando esta intenção de pagamento vale.
 *
 * ## O que a coluna significa
 *
 * `payment_expires_at` é o **instante objetivo** até o qual aquela intenção de
 * pagamento permanece válida, informado pelo gateway. Não é idade do pedido,
 * não é `created_at + N`, não é timeout da aplicação.
 *
 * Por isso `NULL` não quer dizer "expirado" nem "expira já": quer dizer que a
 * aplicação **não tem evidência suficiente** para expirar aquele pedido
 * automaticamente. Pedido manual, sem gateway, fica assim para sempre — e o
 * varredor simplesmente não o alcança. Todo pedido histórico nasce `NULL`, sem
 * backfill: inventar um prazo retroativo expiraria vendas que ninguém autorizou
 * expirar.
 *
 * ## Por que o índice é composto, nesta ordem
 *
 * O varredor pergunta sempre a mesma coisa:
 *
 *     status = 'aguardando_pagamento'
 *     AND payment_expires_at IS NOT NULL
 *     AND payment_expires_at <= ?
 *
 * `status` vem primeiro por ser igualdade; a faixa temporal vem depois. Pedido
 * com `NULL` não entra no intervalo e sai do caminho sem custo — o que torna a
 * ausência de prazo barata, e não uma exceção a filtrar em PHP.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('payment_expires_at')->nullable()->after('paid_at');

            $table->index(['status', 'payment_expires_at'], 'orders_status_payment_expires_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_status_payment_expires_at_index');
            $table->dropColumn('payment_expires_at');
        });
    }
};
