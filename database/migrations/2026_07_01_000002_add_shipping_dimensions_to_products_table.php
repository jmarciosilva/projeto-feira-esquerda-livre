<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'weight')) {
                $table->decimal('weight', 8, 3)->nullable()->after('price');
            }

            if (! Schema::hasColumn('products', 'height')) {
                $table->decimal('height', 8, 2)->nullable()->after('weight');
            }

            if (! Schema::hasColumn('products', 'width')) {
                $table->decimal('width', 8, 2)->nullable()->after('height');
            }

            if (! Schema::hasColumn('products', 'length')) {
                $table->decimal('length', 8, 2)->nullable()->after('width');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $columns = collect(['length', 'width', 'height', 'weight'])
                ->filter(fn (string $column) => Schema::hasColumn('products', $column))
                ->all();

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
