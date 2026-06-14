<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('expositor_id')
                  ->constrained('content_categories')->nullOnDelete();
            $table->boolean('has_stock')->default(true)->after('is_active');
            $table->unsignedInteger('stock_quantity')->nullable()->after('has_stock');
            $table->json('images')->nullable()->after('image_path');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn(['category_id', 'has_stock', 'stock_quantity', 'images']);
        });
    }
};
