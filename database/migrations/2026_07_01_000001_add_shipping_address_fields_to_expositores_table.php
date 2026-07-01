<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expositores', function (Blueprint $table) {
            if (! Schema::hasColumn('expositores', 'zipcode')) {
                $table->string('zipcode', 9)->nullable()->after('image_path');
            }

            if (! Schema::hasColumn('expositores', 'street')) {
                $table->string('street')->nullable()->after('zipcode');
            }

            if (! Schema::hasColumn('expositores', 'number')) {
                $table->string('number', 20)->nullable()->after('street');
            }

            if (! Schema::hasColumn('expositores', 'district')) {
                $table->string('district')->nullable()->after('number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('expositores', function (Blueprint $table) {
            $columns = collect(['district', 'number', 'street', 'zipcode'])
                ->filter(fn (string $column) => Schema::hasColumn('expositores', $column))
                ->all();

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
