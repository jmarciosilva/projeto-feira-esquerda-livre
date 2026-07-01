<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_profiles', function (Blueprint $table) {
            $table->boolean('marketing_opt_in')->default(true)->after('marketplace_status');
            $table->timestamp('marketing_opt_in_at')->nullable()->after('marketing_opt_in');
        });
    }

    public function down(): void
    {
        Schema::table('customer_profiles', function (Blueprint $table) {
            $table->dropColumn(['marketing_opt_in', 'marketing_opt_in_at']);
        });
    }
};
