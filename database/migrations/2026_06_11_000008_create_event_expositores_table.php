<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_expositores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('expositor_id')->constrained('expositores')->cascadeOnDelete();
            $table->enum('status', ['pendente', 'confirmado'])->default('confirmado');
            $table->timestamps();

            $table->unique(['event_id', 'expositor_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_expositores');
    }
};
