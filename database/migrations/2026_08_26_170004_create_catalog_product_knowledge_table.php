<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CAT-03 — ponte entre o item comercial e o conhecimento.
 *
 * Só a CAPACIDADE estrutural: a CAT-03 não infere associação nenhuma a partir
 * do texto do lojista. Associar "Tapete de crochê" a Crochê e a Artesanato é
 * trabalho das fases seguintes, e passará pela mesma governança do resto — por
 * isso a ponte carrega `source` própria, e não herda a do conceito.
 *
 * Um conceito ligado a um produto por curadoria humana vale mais do que o
 * mesmo conceito ligado por inferência automática, ainda que o conceito em si
 * seja idêntico.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_product_knowledge', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();
            $table->foreignId('knowledge_entry_id')
                ->constrained('catalog_knowledge_entries')
                ->cascadeOnDelete();
            $table->string('source', 30);
            $table->decimal('confidence', 3, 2)->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'knowledge_entry_id'], 'catalog_product_knowledge_unique');
            $table->index('knowledge_entry_id', 'catalog_product_knowledge_entry_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_product_knowledge');
    }
};
