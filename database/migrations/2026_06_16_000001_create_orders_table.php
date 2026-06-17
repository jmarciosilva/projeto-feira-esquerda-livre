<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 12)->unique();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('session_id')->nullable()->index();

            $table->string('customer_name');
            $table->string('customer_whatsapp');
            $table->string('customer_email')->nullable();

            $table->string('address_cep', 9);
            $table->string('address_rua');
            $table->string('address_numero', 20);
            $table->string('address_complemento')->nullable();
            $table->string('address_bairro');
            $table->string('address_cidade');
            $table->string('address_estado', 2);

            $table->decimal('items_total', 10, 2);
            $table->decimal('shipping_total', 10, 2)->default(0);
            $table->text('shipping_note')->nullable();
            $table->decimal('total_amount', 10, 2);

            $table->string('status')->default('aguardando_pagamento');
            $table->text('notes')->nullable();
            $table->timestamp('paid_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
