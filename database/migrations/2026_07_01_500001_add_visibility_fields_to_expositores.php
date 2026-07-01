<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expositores', function (Blueprint $table) {
            $table->unsignedSmallInteger('home_rotation_weight')->default(1)->after('sort_order');
            $table->unsignedInteger('total_impressions')->default(0)->after('home_rotation_weight');
        });
    }

    public function down(): void
    {
        Schema::table('expositores', function (Blueprint $table) {
            $table->dropColumn(['home_rotation_weight', 'total_impressions']);
        });
    }
};
