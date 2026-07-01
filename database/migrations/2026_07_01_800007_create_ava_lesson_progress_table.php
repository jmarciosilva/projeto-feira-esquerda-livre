<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ava_lesson_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained('ava_enrollments')->cascadeOnDelete();
            $table->foreignId('lesson_id')->constrained('ava_lessons')->cascadeOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('watched_seconds')->default(0);
            $table->unsignedInteger('last_position_sec')->default(0)->comment('retomar do onde parou');
            $table->timestamps();

            $table->unique(['enrollment_id', 'lesson_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ava_lesson_progress');
    }
};
