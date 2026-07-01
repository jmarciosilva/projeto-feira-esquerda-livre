<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ava_lesson_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained('ava_lessons')->cascadeOnDelete();
            $table->string('title');
            $table->string('file_path')->comment('path no storage — nunca URL pública direta');
            $table->string('file_type')->default('pdf')->comment('pdf|audio|image|spreadsheet|other');
            $table->unsignedInteger('file_size_kb')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['lesson_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ava_lesson_materials');
    }
};
