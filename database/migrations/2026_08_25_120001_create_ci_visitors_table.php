<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Modulo interno de Customer Intelligence (fase CI-02).
 *
 * Visitante conhecido pelo sistema, autenticado ou nao. O `visitor_uuid` e o
 * identificador publico/tecnico; o `id` continua sendo a chave interna usada
 * pelos relacionamentos.
 *
 * Minimizacao (LGPD): nenhum dado pessoal e copiado para ca. Nome, e-mail e
 * telefone continuam vivendo apenas em `users`, alcancados por `user_id`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ci_visitors', function (Blueprint $table) {
            $table->id();
            $table->uuid('visitor_uuid')->unique();

            // Preenchido quando o visitante anonimo se autentica. Se a conta
            // for excluida, o visitante permanece anonimo em vez de sumir.
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();

            // Metadados comportamentais minimos (ex.: primeira origem de trafego).
            // Nunca dados pessoais.
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index('last_seen_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ci_visitors');
    }
};
