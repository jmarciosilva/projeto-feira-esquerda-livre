<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FIN-SEC-01F-D — quando a reversão aconteceu, sem apagar quando o pagamento
 * aconteceu.
 *
 * ## Por que não reaproveitar `paid_at`
 *
 * `paid_at` responde **"quando este pedido foi pago?"**, e a resposta continua
 * verdadeira depois do estorno: o dinheiro entrou, e depois voltou. Zerar o
 * campo — ou pior, sobrescrevê-lo com o instante da reversão — apagaria o
 * primeiro fato para registrar o segundo, e é assim que se perde a capacidade
 * de responder quanto tempo o dinheiro ficou na plataforma.
 *
 * São dois fatos, e por isso duas colunas.
 *
 * ## Duas colunas, e não uma
 *
 * `orders.reversed_at` marca a reversão do pedido; `order_splits.reverted_at`
 * marca a do repasse daquele vendedor. Poderiam ser o mesmo instante hoje,
 * quando toda reversão é total — mas o split é a unidade de repasse, e o dia em
 * que a plataforma souber reverter a participação de um vendedor sem reverter a
 * do outro, a coluna já estará no lugar certo.
 *
 * Sem backfill: pedido histórico fica nulo, que é a verdade — nenhum deles
 * sofreu reversão registrada por este domínio.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('reversed_at')->nullable()->after('paid_at');
        });

        Schema::table('order_splits', function (Blueprint $table) {
            $table->timestamp('reverted_at')->nullable()->after('confirmed_at');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('reversed_at');
        });

        Schema::table('order_splits', function (Blueprint $table) {
            $table->dropColumn('reverted_at');
        });
    }
};
