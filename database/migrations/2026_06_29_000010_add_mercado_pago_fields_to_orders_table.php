<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_method')->default('manual')->after('total_amount');
            $table->string('payment_provider')->nullable()->after('payment_method');
            $table->string('payment_status')->nullable()->after('payment_provider');
            $table->string('mercado_pago_preference_id')->nullable()->after('payment_status')->index();
            $table->string('mercado_pago_payment_id')->nullable()->after('mercado_pago_preference_id')->index();
            $table->text('mercado_pago_init_point')->nullable()->after('mercado_pago_payment_id');
            $table->text('mercado_pago_sandbox_init_point')->nullable()->after('mercado_pago_init_point');
            $table->json('payment_payload')->nullable()->after('mercado_pago_sandbox_init_point');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['mercado_pago_preference_id']);
            $table->dropIndex(['mercado_pago_payment_id']);
            $table->dropColumn([
                'payment_method',
                'payment_provider',
                'payment_status',
                'mercado_pago_preference_id',
                'mercado_pago_payment_id',
                'mercado_pago_init_point',
                'mercado_pago_sandbox_init_point',
                'payment_payload',
            ]);
        });
    }
};
