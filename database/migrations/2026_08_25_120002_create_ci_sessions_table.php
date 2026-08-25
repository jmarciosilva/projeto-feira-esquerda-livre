<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Modulo interno de Customer Intelligence (fase CI-02).
 *
 * Sessao de navegacao de um visitante. A janela real (30 minutos rolantes) e
 * decidida pelo middleware que sera criado na CI-04; aqui fica apenas a
 * estrutura.
 *
 * O nome da tabela e `ci_sessions` para nao colidir com `sessions`, que e a
 * tabela de sessao do proprio Laravel (SESSION_DRIVER=database).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ci_sessions', function (Blueprint $table) {
            $table->id();
            $table->uuid('session_uuid')->unique();

            $table->foreignId('visitor_id')->constrained('ci_visitors')->cascadeOnDelete();

            $table->timestamp('started_at');
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamp('ended_at')->nullable();

            // Origem da sessao. Guardamos apenas o caminho da landing e as UTMs
            // — sem query string completa, que pode carregar dado pessoal.
            $table->string('landing_url', 512)->nullable();
            $table->string('referrer', 512)->nullable();
            $table->string('utm_source', 128)->nullable();
            $table->string('utm_medium', 128)->nullable();
            $table->string('utm_campaign', 128)->nullable();

            $table->timestamps();

            $table->index('started_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ci_sessions');
    }
};
