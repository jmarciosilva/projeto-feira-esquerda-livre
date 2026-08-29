<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FIN-SEC-01F-C.2 — até quando um pedido sem intenção de pagamento segura o
 * estoque.
 *
 * ## Por que não reaproveitar `payment_expires_at`
 *
 * Aquela coluna significa **o prazo objetivo de uma intenção de pagamento que
 * existe**, informado pelo gateway. Preenchê-la com `created_at + janela`
 * quando nenhuma intenção foi criada seria atribuir ao Mercado Pago uma
 * informação que ele nunca deu — e destruiria a única propriedade que torna
 * aquele campo confiável.
 *
 * `checkout_expires_at` é outra coisa, e por isso é outra coluna: uma regra
 * interna da plataforma sobre quanto tempo a reserva pode ficar de pé enquanto
 * o cliente ainda não iniciou o pagamento. Aqui a duração configurada é
 * legítima, porque a plataforma é a autoridade sobre o próprio checkout.
 *
 * ## Precedência
 *
 * Quando as duas existem, manda `payment_expires_at`: a intenção externa nasceu
 * e o prazo dela é o que vale. A janela interna governa só o intervalo em que
 * ainda não há intenção nenhuma.
 *
 * ## Índice
 *
 * O varredor passa a ter dois caminhos, e cada um precisa do seu. Este cobre a
 * varredura por janela interna; `orders_status_payment_expires_at_index`, de
 * FIN-SEC-01F-C, continua cobrindo o prazo do gateway.
 *
 * Sem backfill: pedido histórico fica com as duas colunas nulas e não expira.
 * Inventar prazo retroativo cancelaria vendas que ninguém autorizou cancelar.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('checkout_expires_at')->nullable()->after('payment_expires_at');

            $table->index(['status', 'checkout_expires_at'], 'orders_status_checkout_expires_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_status_checkout_expires_at_index');
            $table->dropColumn('checkout_expires_at');
        });
    }
};
