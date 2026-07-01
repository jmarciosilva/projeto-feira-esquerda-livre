<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ava_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('course_id')->constrained('ava_courses')->cascadeOnDelete();
            $table->foreignId('order_split_id')->nullable()->constrained('order_splits')->nullOnDelete();
            $table->string('status')->default('active')->comment('active|expired|cancelled|refunded');
            $table->timestamp('enrolled_at')->useCurrent();
            $table->timestamp('expires_at')->nullable()->comment('null = acesso perpétuo');
            $table->timestamp('completed_at')->nullable();
            $table->decimal('completion_percent', 5, 2)->default(0);
            $table->timestamp('last_accessed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'course_id']);
            $table->index(['user_id', 'status']);
            $table->index(['course_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ava_enrollments');
    }
};
