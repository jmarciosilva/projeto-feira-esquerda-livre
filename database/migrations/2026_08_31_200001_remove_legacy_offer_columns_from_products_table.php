<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CAT-DOM-02H — os doze espelhos comerciais saem de `products`.
 *
 * ## O que estas colunas eram
 *
 * Até a CAT-DOM-02C, cada campo comercial era gravado **nos dois lados**:
 * `product_offers` como fonte de verdade e `products` como cópia, para que
 * nenhuma consulta antiga lesse preço diferente do que a oferta cobrava. A 02C
 * encerrou a escrita; as colunas ficaram, congeladas no valor do cutover.
 *
 * A 02D deu à oferta o conteúdo, a 02E migrou writers e readers, a 02F o
 * ownership e a 02G a seleção. Nada mais lê estas doze colunas — e é isso que
 * esta migration finalmente cobra.
 *
 * ## O que sai, e por quê
 *
 * Exatamente `SaveProductWithOffer::ESPELHOS_COMERCIAIS_LEGADOS`. Não é uma
 * lista nova: é a mesma que a 02C nomeou, que a `ProductFactory` já removia do
 * produto e que o trait de seed já excluía. A auditoria da 02H confirmou zero
 * writer e zero reader de runtime para cada uma.
 *
 * ## O que **não** sai, e por quê
 *
 * - `is_active` — validade canônica do item, da curadoria (D-CAT-10). Nunca foi
 *   espelho: existe nas duas tabelas com significados diferentes;
 * - `expositor_id` — proveniência (D-CAT-11), registro de quem trouxe o item.
 *   Não é ownership, e ownership não é motivo para apagá-la;
 * - `images` e `image_path` — imagem **canônica** do catálogo, que a 02E
 *   preservou de propósito e que o fallback de leitura ainda usa;
 * - `slug`, `name`, `description`, `short_description`, `item_type`,
 *   `category_id`, `is_digital` e a delegação canônica — identidade do item.
 *
 * ## Sobre o rollback
 *
 * O `down()` recria a **estrutura**: mesmo tipo, mesma nullability, mesmo
 * default, mesma posição e o índice de `is_featured`, que o MySQL derruba junto
 * com a coluna.
 *
 * > **Rollback de schema não é restauração de dados.** Os valores destas doze
 * > colunas são apagados aqui e não voltam: o `down()` devolve colunas vazias.
 * > Isso é aceitável porque a verdade comercial vive em `product_offers` desde
 * > a 02C — a auditoria confirmou paridade exata, sem uma única divergência
 * > entre espelho e oferta nos 75 itens do banco de desenvolvimento.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // O índice sai antes da coluna: em MySQL ele cairia junto, mas
            // dizê-lo aqui mantém o `up()` legível e não depende do motor.
            $table->dropIndex('products_is_featured_index');

            $table->dropColumn([
                'price',
                'price_type',
                'modality',
                'duration_min',
                'weight',
                'height',
                'width',
                'length',
                'has_stock',
                'stock_quantity',
                'is_featured',
                'sort_order',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Tipos, nullability e defaults conferidos no schema real antes do
            // drop, coluna a coluna. A ordem reproduz a original.
            $table->decimal('price', 10, 2)->nullable()->after('images');
            $table->decimal('weight', 8, 3)->nullable()->after('price');
            $table->decimal('height', 8, 2)->nullable()->after('weight');
            $table->decimal('width', 8, 2)->nullable()->after('height');
            $table->decimal('length', 8, 2)->nullable()->after('width');
            $table->string('price_type')->nullable()->after('length');
            $table->string('modality')->nullable()->after('price_type');
            $table->unsignedSmallInteger('duration_min')->nullable()->after('modality');
            $table->boolean('is_featured')->default(false)->after('duration_min');
            $table->boolean('has_stock')->default(true)->after('is_digital');
            $table->unsignedInteger('stock_quantity')->nullable()->after('has_stock');
            $table->unsignedSmallInteger('sort_order')->default(0)->after('stock_quantity');

            $table->index('is_featured', 'products_is_featured_index');
        });
    }
};
