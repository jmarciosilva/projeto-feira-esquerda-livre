<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_shippings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_split_id')->unique()->constrained('order_splits')->cascadeOnDelete();
            $table->foreignId('expositor_id')->constrained('expositores')->cascadeOnDelete();
            $table->string('carrier')->nullable();
            $table->string('service_name')->nullable();
            $table->string('tracking_code')->nullable()->index();
            $table->decimal('price', 10, 2)->default(0);
            $table->unsignedSmallInteger('estimated_days')->nullable();
            $table->string('status')->default('pending');
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_shippings');
    }
};
