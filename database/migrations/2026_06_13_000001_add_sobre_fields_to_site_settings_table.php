<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->string('sobre_titulo')->nullable()->after('footer_text');
            $table->text('sobre_texto')->nullable()->after('sobre_titulo');
            $table->string('sobre_imagem_path')->nullable()->after('sobre_texto');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['sobre_titulo', 'sobre_texto', 'sobre_imagem_path']);
        });
    }
};
