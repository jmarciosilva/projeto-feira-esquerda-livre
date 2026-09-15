<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CAT-10A.1 — configuração do provider externo do Catalog Intelligence pelo painel.
 *
 * O banco passa a ser a única autoridade operacional do provider: ligado, provider,
 * modelo, chave e prazo. A chave é gravada pelo cast `encrypted` de `SiteSetting`,
 * por isso `text`. Nasce desligado.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->boolean('catalog_ai_ativo')->default(false)->after('mercado_pago_sandbox');
            $table->string('catalog_ai_provider')->nullable()->after('catalog_ai_ativo');
            $table->string('catalog_ai_modelo', 100)->nullable()->after('catalog_ai_provider');
            $table->text('catalog_ai_api_key')->nullable()->after('catalog_ai_modelo');
            $table->unsignedTinyInteger('catalog_ai_timeout')->nullable()->after('catalog_ai_api_key');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn([
                'catalog_ai_ativo',
                'catalog_ai_provider',
                'catalog_ai_modelo',
                'catalog_ai_api_key',
                'catalog_ai_timeout',
            ]);
        });
    }
};
