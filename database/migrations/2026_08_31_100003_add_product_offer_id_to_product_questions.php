<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CAT-DOM-02D — a pergunta passa a saber em que oferta foi feita.
 *
 * As duas colunas convivem, e nenhuma substitui a outra (D-CAT-17):
 * `product_id` é o agrupamento canônico e o eixo da Catalog Intelligence;
 * `product_offer_id` é o contexto comercial — a oferta onde a pergunta nasceu
 * e o destinatário dela.
 *
 * **Nullable**, por três razões. A D-CAT-17 previu que linha legada mantém
 * contexto nulo; `NOT NULL` exigiria backfill-e-constrain no mesmo movimento, e
 * a 02D é aditiva; e há um caso legítimo de nulo no futuro — pergunta cuja
 * oferta foi removida.
 *
 * **`SET NULL`, nunca `CASCADE`.** A pergunta é conteúdo do cliente e tem valor
 * histórico: o expositor sair da Feira não pode apagar o que o cliente
 * perguntou. É o mesmo tratamento que a FIN-SEC-01B deu a `order_items`, pela
 * mesma razão — a coluna é rastreabilidade, não fonte de verdade.
 *
 * O índice espelha o que já existe para `product_id` e serve às mesmas três
 * consultas: pendentes da loja, respondidas visíveis, contador do painel.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_questions', function (Blueprint $table) {
            $table->foreignId('product_offer_id')
                ->nullable()
                ->after('product_id')
                ->constrained('product_offers')
                ->nullOnDelete();

            $table->index(['product_offer_id', 'answered_at', 'is_visible']);
        });
    }

    public function down(): void
    {
        Schema::table('product_questions', function (Blueprint $table) {
            $table->dropIndex(['product_offer_id', 'answered_at', 'is_visible']);
            $table->dropConstrainedForeignId('product_offer_id');
        });
    }
};
