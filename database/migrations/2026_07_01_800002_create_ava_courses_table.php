<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ava_courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('intro_video_url')->nullable();
            $table->text('requirements')->nullable();
            $table->text('what_youll_learn')->nullable();
            $table->enum('level', ['iniciante', 'intermediario', 'avancado'])->default('iniciante');
            $table->decimal('estimated_hours', 5, 1)->nullable();
            $table->unsignedSmallInteger('access_duration_days')->nullable()->comment('null = acesso perpétuo');
            $table->boolean('is_drip')->default(false)->comment('liberação gradual de aulas por dia');
            $table->boolean('certificate_enabled')->default(true);
            $table->timestamp('published_at')->nullable()->comment('null = rascunho');
            $table->timestamps();

            $table->unique('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ava_courses');
    }
};
