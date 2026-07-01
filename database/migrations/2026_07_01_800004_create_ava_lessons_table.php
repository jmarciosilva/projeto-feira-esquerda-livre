<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ava_lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained('ava_modules')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('content_type')->default('video')->comment('video|pdf|audio|texto');
            $table->string('video_url')->nullable()->comment('URL completa do YouTube ou Vimeo');
            $table->string('video_provider')->nullable()->comment('youtube|vimeo|upload');
            $table->unsignedInteger('video_duration_sec')->nullable();
            $table->longText('text_content')->nullable()->comment('para content_type=texto');
            $table->boolean('is_preview')->default(false)->comment('acessível sem matrícula');
            $table->boolean('is_visible')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->unsignedSmallInteger('drip_day')->nullable()->comment('liberar no dia N após matrícula');
            $table->timestamps();

            $table->index(['module_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ava_lessons');
    }
};
