<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lojista_solicitacoes', function (Blueprint $table) {
            $table->id();
            $table->string('nome_loja');
            $table->string('responsavel');
            $table->string('cpf_cnpj', 20);
            $table->string('whatsapp', 20);
            $table->string('email');
            $table->text('descricao')->nullable();
            $table->enum('status', ['pendente', 'aprovado', 'bloqueado'])->default('pendente');
            $table->text('motivo_bloqueio')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index('status');
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lojista_solicitacoes');
    }
};
