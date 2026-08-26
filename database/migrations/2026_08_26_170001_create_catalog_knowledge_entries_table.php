<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CAT-03 — conceito reutilizável do catálogo.
 *
 * Vive fora de `products` de propósito: `products` é o item comercial de um
 * lojista; isto é conhecimento coletivo da Feira, que nasce de um item mas
 * passa a valer para os próximos.
 *
 * A unicidade é `(type, normalized_name)` e é do BANCO, não de um
 * `if (! exists()) create()` — duas requisições simultâneas cadastrando
 * "Crochê" e "crochê" precisam colidir de verdade, não por sorte de timing.
 * O par inclui o tipo porque a mesma palavra pode ser conceitos diferentes:
 * "Cerâmica" é técnica e também é material.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalog_knowledge_entries', function (Blueprint $table) {
            $table->id();
            $table->string('type', 40);
            $table->string('name', 160);
            $table->string('normalized_name', 160);
            $table->text('description')->nullable();
            $table->string('status', 30);
            $table->string('source', 30);
            // Nullable e sem preenchimento automático: na CAT-03 a confiança é
            // ordinal e vive em KnowledgeSource::trustLevel(). Este campo fica
            // reservado para valores derivados das fases seguintes — inventar
            // um número agora seria pseudo-métrica.
            $table->decimal('confidence', 3, 2)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->unique(['type', 'normalized_name'], 'catalog_knowledge_entries_type_norm_unique');
            // A leitura dominante das próximas fases é "conceitos aprovados
            // deste tipo", não a varredura da tabela inteira.
            $table->index(['status', 'type'], 'catalog_knowledge_entries_status_type_index');
            $table->index('normalized_name', 'catalog_knowledge_entries_norm_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalog_knowledge_entries');
    }
};
