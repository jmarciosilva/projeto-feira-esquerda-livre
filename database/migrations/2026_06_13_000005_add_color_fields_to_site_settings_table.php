<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->string('color_primary')->default('#E8A000')->after('footer_text');
            $table->string('color_primary_dark')->default('#C47A00')->after('color_primary');
            $table->string('color_secondary')->default('#F4E294')->after('color_primary_dark');
            $table->string('color_secondary_light')->default('#FDF8DC')->after('color_secondary');
            $table->string('color_dark')->default('#3D3000')->after('color_secondary_light');
        });
    }

    public function down(): void
    {
        Schema::table('site_settings', function (Blueprint $table) {
            $table->dropColumn([
                'color_primary', 'color_primary_dark',
                'color_secondary', 'color_secondary_light', 'color_dark',
            ]);
        });
    }
};
