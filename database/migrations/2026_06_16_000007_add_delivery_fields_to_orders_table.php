<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('delivery_type')->default('entrega')->after('customer_email');
            $table->foreignId('customer_address_id')->nullable()->after('delivery_type')
                ->constrained('customer_addresses')->nullOnDelete();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('address_cep', 9)->nullable()->change();
            $table->string('address_rua')->nullable()->change();
            $table->string('address_numero', 20)->nullable()->change();
            $table->string('address_bairro')->nullable()->change();
            $table->string('address_cidade')->nullable()->change();
            $table->string('address_estado', 2)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('customer_address_id');
            $table->dropColumn('delivery_type');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('address_cep', 9)->nullable(false)->change();
            $table->string('address_rua')->nullable(false)->change();
            $table->string('address_numero', 20)->nullable(false)->change();
            $table->string('address_bairro')->nullable(false)->change();
            $table->string('address_cidade')->nullable(false)->change();
            $table->string('address_estado', 2)->nullable(false)->change();
        });
    }
};
