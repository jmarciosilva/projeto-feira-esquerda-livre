<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Produtos: tipo de item, tipo de preço, modalidade e duração
        Schema::table('products', function (Blueprint $table) {
            $table->string('item_type')->default('produto')->after('id');
            $table->string('price_type')->nullable()->after('price');
            $table->string('modality')->nullable()->after('price_type');
            $table->unsignedSmallInteger('duration_min')->nullable()->after('modality');
        });

        // Categorias: eixo ao qual pertencem
        Schema::table('content_categories', function (Blueprint $table) {
            $table->string('eixo')->nullable()->after('name');
        });

        // Expositores: eixos em que atuam (JSON, pois um lojista pode atuar em vários)
        Schema::table('expositores', function (Blueprint $table) {
            $table->json('eixos')->nullable()->after('description');
        });

        // Solicitações: eixos declarados pelo candidato
        Schema::table('lojista_solicitacoes', function (Blueprint $table) {
            $table->json('eixos')->nullable()->after('descricao');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['item_type', 'price_type', 'modality', 'duration_min']);
        });

        Schema::table('content_categories', function (Blueprint $table) {
            $table->dropColumn('eixo');
        });

        Schema::table('expositores', function (Blueprint $table) {
            $table->dropColumn('eixos');
        });

        Schema::table('lojista_solicitacoes', function (Blueprint $table) {
            $table->dropColumn('eixos');
        });
    }
};
