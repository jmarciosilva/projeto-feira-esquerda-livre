<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CAT-DOM-02D — a FAQ comercial ganha tabela própria.
 *
 * `product_faqs` permanece existindo e passa a significar **FAQ canônica** —
 * afirmação do catálogo, sob curadoria (D-CAT-16). O que o expositor escreve
 * sobre a própria oferta mora aqui.
 *
 * Duas tabelas em vez de `product_faqs` com FK dupla e invariante XOR: as duas
 * FKs continuam `NOT NULL`, cada tabela significa uma coisa só, e o isolamento
 * da 02F fica trivial — a FAQ da oferta já é da oferta.
 *
 * `UNIQUE(product_offer_id, sort_order)`, e não índice comum. `sort_order` é
 * **posição**, e duas FAQs não podem ocupar a mesma posição dentro de uma
 * oferta. Em `product_faqs` essa unicidade é acidental: vem de o writer legado
 * atribuir o índice do array, nunca de o banco exigir. Aqui ela é invariante de
 * schema, e é o que sustenta a idempotência da reconciliação pré-cutover.
 *
 * A `UNIQUE` substitui o índice comum — serve às mesmas consultas
 * (`WHERE product_offer_id = ? ORDER BY sort_order`), então não há índice
 * redundante a criar.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_offer_faqs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_offer_id')->constrained('product_offers')->cascadeOnDelete();
            $table->string('question');
            $table->text('answer');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['product_offer_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        // Seguro: a origem legada continua intacta durante a 02D — o backfill
        // copia `product_faqs`, nunca a esvazia. Desfazer aqui não perde
        // nenhum dado que só exista neste lugar.
        Schema::dropIfExists('product_offer_faqs');
    }
};
