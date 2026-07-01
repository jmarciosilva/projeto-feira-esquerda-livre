<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->enum('marketplace_status', ['active', 'inactive'])->default('active');
            $table->timestamps();
        });

        // Backfill: cria perfil para todos os usuários existentes com papel 'user'
        DB::table('customer_profiles')->insertUsing(
            ['user_id', 'marketplace_status', 'created_at', 'updated_at'],
            DB::table('users')
                ->select('id', DB::raw("'active'"), DB::raw('CURRENT_TIMESTAMP'), DB::raw('CURRENT_TIMESTAMP'))
                ->where('role', 'user')
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_profiles');
    }
};
