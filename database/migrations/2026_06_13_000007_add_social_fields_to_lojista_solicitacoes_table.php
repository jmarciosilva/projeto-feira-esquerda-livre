<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lojista_solicitacoes', function (Blueprint $table) {
            $table->string('instagram_url')->nullable()->after('email');
            $table->string('facebook_url')->nullable()->after('instagram_url');
        });
    }

    public function down(): void
    {
        Schema::table('lojista_solicitacoes', function (Blueprint $table) {
            $table->dropColumn(['instagram_url', 'facebook_url']);
        });
    }
};
