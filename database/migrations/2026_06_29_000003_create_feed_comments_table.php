<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feed_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('feed_post_id')->constrained('feed_posts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('content', 500);
            $table->boolean('is_visible')->default(true)->index();
            $table->timestamps();

            $table->index(['feed_post_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feed_comments');
    }
};
