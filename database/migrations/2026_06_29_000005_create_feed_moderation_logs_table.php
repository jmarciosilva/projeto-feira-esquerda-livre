<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feed_moderation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('feed_post_id')->constrained('feed_posts')->cascadeOnDelete();
            $table->foreignId('admin_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 30);
            $table->string('reason', 500)->nullable();
            $table->timestamps();

            $table->index(['feed_post_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feed_moderation_logs');
    }
};
