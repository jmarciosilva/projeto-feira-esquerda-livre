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
            $table->text('melhor_envio_refresh_token')->nullable()->after('melhor_envio_token');
            $table->timestamp('melhor_envio_token_expires_at')->nullable()->after('melhor_envio_refresh_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn(['melhor_envio_refresh_token', 'melhor_envio_token_expires_at']);
        });
    }
};
