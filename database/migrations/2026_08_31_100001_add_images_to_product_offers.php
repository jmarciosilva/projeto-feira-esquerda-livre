<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CAT-DOM-02D — a oferta ganha onde guardar a própria imagem.
 *
 * Coluna JSON, e não tabela `product_offer_images`: são no máximo quatro
 * imagens sem metadado próprio, e o formato espelha exatamente o de
 * `products.images` — `[{"thumb": path, "medium": path}]` —, o que mantém os
 * treze pontos de leitura da 02E com uma única forma a aprender.
 *
 * `image_path` **não** é replicado aqui de propósito: ele é espelho legado do
 * primeiro thumb (D-1, remoção na 02H), e importar a dívida para a estrutura
 * nova seria criar em 2026 o problema que a 02H vai apagar.
 *
 * Nada lê nem escreve esta coluna ao fim da 02D. O consumidor é a 02E.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_offers', function (Blueprint $table) {
            $table->json('images')->nullable()->after('expositor_id');
        });
    }

    public function down(): void
    {
        // Só o schema volta. Os arquivos que o backfill copiou permanecem em
        // disco — ver `catalog:backfill-offer-content` e §27.2 da especificação:
        // não existe rollback atômico de banco e disco, e apagar bytes num
        // rollback de schema seria destruir o que o banco não sabe restaurar.
        Schema::table('product_offers', function (Blueprint $table) {
            $table->dropColumn('images');
        });
    }
};
