<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CAT-03 — ligações entre conceitos.
 *
 * A relação é DIRIGIDA e gravada uma vez só, na direção em que foi escrita:
 * "Crochê é técnica de Artesanato" não é a mesma frase que a inversa. Mesmo
 * `related_to`, que se lê como simétrico, fica gravado num sentido só — quem
 * percorrer o grafo (CAT-04) é que decide olhar os dois lados. Gravar o par
 * duplicado seria criar duas fontes da mesma verdade.
 *
 * O índice em (to_entry_id, relation_type) é o que torna essa leitura inversa
 * barata.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_knowledge_relations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_entry_id')
                ->constrained('catalog_knowledge_entries')
                ->cascadeOnDelete();
            $table->foreignId('to_entry_id')
                ->constrained('catalog_knowledge_entries')
                ->cascadeOnDelete();
            $table->string('relation_type', 40);
            $table->decimal('weight', 3, 2)->nullable();
            $table->timestamps();

            $table->unique(['from_entry_id', 'to_entry_id', 'relation_type'], 'catalog_knowledge_relations_unique');
            $table->index(['to_entry_id', 'relation_type'], 'catalog_knowledge_relations_reverse_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_knowledge_relations');
    }
};
