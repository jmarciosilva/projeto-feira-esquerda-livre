<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feed_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expositor_id')->constrained('expositores')->cascadeOnDelete();
            $table->string('type', 40);
            $table->string('content', 500);
            $table->json('images')->nullable();
            $table->boolean('is_visible')->default(true)->index();
            $table->unsignedInteger('reported_count')->default(0);
            $table->timestamps();

            $table->index(['expositor_id', 'created_at']);
            $table->index(['is_visible', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feed_posts');
    }
};
