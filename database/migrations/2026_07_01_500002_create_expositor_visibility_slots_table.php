<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expositor_visibility_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expositor_id')->constrained('expositores')->cascadeOnDelete();
            $table->string('slot_type')->default('home_rotation');
            $table->unsignedSmallInteger('priority')->default(0);
            $table->timestamp('active_from')->nullable();
            $table->timestamp('active_until')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['expositor_id', 'slot_type']);
            $table->index(['slot_type', 'priority', 'active_from', 'active_until'], 'evs_type_priority_window_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expositor_visibility_slots');
    }
};
