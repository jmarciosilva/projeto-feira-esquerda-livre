<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CAT-03 — as outras formas pelas quais um conceito é escrito ou procurado.
 *
 * "Feito à mão" e "artesanal" apontam para o mesmo conceito; quem cadastra
 * escreve um, quem procura escreve o outro. O termo é o que torna o conceito
 * alcançável sem que o texto do lojista precise bater exatamente com o nome
 * canônico.
 *
 * O índice em `normalized_term` existe para a busca por termo das fases
 * seguintes; ele NÃO é único globalmente, porque o mesmo termo pode
 * legitimamente pertencer a conceitos de tipos diferentes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_knowledge_terms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('knowledge_entry_id')
                ->constrained('catalog_knowledge_entries')
                ->cascadeOnDelete();
            $table->string('term', 160);
            $table->string('normalized_term', 160);
            $table->string('type', 30);
            $table->timestamps();

            $table->unique(['knowledge_entry_id', 'normalized_term'], 'catalog_knowledge_terms_entry_norm_unique');
            $table->index('normalized_term', 'catalog_knowledge_terms_norm_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_knowledge_terms');
    }
};
