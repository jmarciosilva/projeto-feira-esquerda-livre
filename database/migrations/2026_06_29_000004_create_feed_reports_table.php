<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feed_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('feed_post_id')->constrained('feed_posts')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reason', 500);
            $table->string('status', 20)->default('pendente')->index();
            $table->timestamps();

            $table->unique(['feed_post_id', 'user_id']);
            $table->index(['feed_post_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feed_reports');
    }
};
