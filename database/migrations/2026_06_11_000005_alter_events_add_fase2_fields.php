<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('banner_image_path')->nullable()->after('image_path');
            $table->unsignedInteger('capacidade_expositores')->nullable()->after('registration_url');
            $table->unsignedInteger('vagas_restantes')->nullable()->after('capacidade_expositores');
            $table->boolean('is_featured')->default(false)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['banner_image_path', 'capacidade_expositores', 'vagas_restantes', 'is_featured']);
        });
    }
};
