<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CAT-DOM-01 — a oferta comercial de um expositor sobre um item de catálogo.
 *
 * `products` respondia a duas perguntas ao mesmo tempo: *o que é este item?* e
 * *quem vende, por quanto e em que condições?*. A primeira pertence ao
 * catálogo e sobrevive à saída de um lojista; a segunda morre com ela. Esta
 * tabela recebe a segunda.
 *
 * As colunas aqui foram escolhidas uma a uma pela matriz da CAT-DOM-01A, não
 * por semelhança de nome. `weight`/`height`/`width`/`length` vêm para cá porque
 * quem embala e despacha é o expositor; `modality` e `duration_min` porque
 * descrevem como *aquele* prestador atende. `name`, `description`, `category_id`
 * e `is_digital` ficaram em `products` porque respondem à primeira pergunta.
 *
 * `expositor_id` é `cascadeOnDelete` de propósito: excluir um expositor apaga
 * as ofertas dele e **não toca nos produtos**. O item continua no catálogo, o
 * conhecimento da Catalog Intelligence continua associado a ele, e o item
 * simplesmente deixa de ter quem o venda — que é a decisão comercial inteira
 * desta fase, em uma linha de schema.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_offers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            $table->foreignId('expositor_id')
                ->constrained('expositores')
                ->cascadeOnDelete();

            $table->decimal('price', 10, 2)->nullable();
            $table->string('price_type')->nullable();
            $table->string('modality')->nullable();
            $table->unsignedSmallInteger('duration_min')->nullable();

            $table->decimal('weight', 8, 3)->nullable();
            $table->decimal('height', 8, 2)->nullable();
            $table->decimal('width', 8, 2)->nullable();
            $table->decimal('length', 8, 2)->nullable();

            $table->boolean('has_stock')->default(true);
            $table->unsignedInteger('stock_quantity')->nullable();

            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->timestamps();

            // Um expositor tem no máximo uma oferta por item de catálogo.
            // Variação de material, medida ou acabamento é outro produto —
            // esta fase não funde nem desdobra nada automaticamente.
            $table->unique(['product_id', 'expositor_id'], 'product_offers_product_expositor_unique');

            // A vitrine da loja e o painel do lojista sempre filtram por
            // expositor + status; o catálogo por eixo e a home, por status.
            $table->index(['expositor_id', 'is_active'], 'product_offers_expositor_active_index');
            $table->index(['is_active', 'is_featured'], 'product_offers_active_featured_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_offers');
    }
};
