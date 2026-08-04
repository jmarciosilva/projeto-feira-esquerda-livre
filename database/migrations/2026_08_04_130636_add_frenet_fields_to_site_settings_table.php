<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->boolean('frenet_ativo')->default(false)->after('melhor_envio_sandbox');
            $table->text('frenet_token')->nullable()->after('frenet_ativo');
            // Mantem o comportamento atual em producao (Melhor Envio) ate alguem trocar manualmente.
            $table->string('frete_provedor')->default('melhor_envio')->after('frenet_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['frenet_ativo', 'frenet_token', 'frete_provedor']);
        });
    }
};
