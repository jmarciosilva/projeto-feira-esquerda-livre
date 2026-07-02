<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ava_enrollments', function (Blueprint $table) {
            $table->string('certificate_path')->nullable()->after('completion_percent');
        });
    }

    public function down(): void
    {
        Schema::table('ava_enrollments', function (Blueprint $table) {
            $table->dropColumn('certificate_path');
        });
    }
};
