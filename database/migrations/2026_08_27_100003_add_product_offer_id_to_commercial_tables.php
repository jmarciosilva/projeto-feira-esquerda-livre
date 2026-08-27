<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * CAT-DOM-01C — carrinho e pedido passam a apontar para a oferta.
 *
 * Nullable e `nullOnDelete`, nunca `cascadeOnDelete`: um pedido histórico não
 * pode desaparecer porque o lojista removeu a oferta anos depois. A integridade
 * do histórico já não dependia do estado vivo do catálogo — `order_items` grava
 * `product_name`, `unit_price` e `expositor_id` no momento da compra —, e esta
 * coluna é rastreabilidade, não fonte de verdade.
 *
 * `cart_items` recebe o mesmo tratamento por simetria e para que o checkout
 * possa reconferir se a oferta ainda está vigente antes de fechar o pedido.
 *
 * O backfill casa `(product_id, expositor_id)` com a oferta correspondente. No
 * banco de desenvolvimento ambas as tabelas estão vazias, mas a migration
 * precisa estar correta para quando não estiverem.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->foreignId('product_offer_id')
                ->nullable()
                ->after('product_id')
                ->constrained('product_offers')
                ->nullOnDelete();
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('product_offer_id')
                ->nullable()
                ->after('product_id')
                ->constrained('product_offers')
                ->nullOnDelete();
        });

        foreach (['cart_items', 'order_items'] as $tabela) {
            DB::table($tabela)
                ->whereNull('product_offer_id')
                ->orderBy('id')
                ->chunkById(200, function ($linhas) use ($tabela) {
                    foreach ($linhas as $linha) {
                        $ofertaId = DB::table('product_offers')
                            ->where('product_id', $linha->product_id)
                            ->where('expositor_id', $linha->expositor_id)
                            ->value('id');

                        if ($ofertaId !== null) {
                            DB::table($tabela)->where('id', $linha->id)->update([
                                'product_offer_id' => $ofertaId,
                            ]);
                        }
                    }
                });
        }
    }

    public function down(): void
    {
        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_offer_id');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_offer_id');
        });
    }
};
