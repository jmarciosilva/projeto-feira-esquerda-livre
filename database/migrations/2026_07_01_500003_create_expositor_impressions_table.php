<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expositor_impressions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expositor_id')->constrained('expositores')->cascadeOnDelete();
            $table->timestamp('rendered_at');
            $table->string('session_hash', 64);
            $table->string('source')->default('home_rotation');
            $table->timestamp('created_at')->useCurrent();

            $table->index(['expositor_id', 'rendered_at']);
            $table->index('rendered_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expositor_impressions');
    }
};
