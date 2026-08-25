<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Modulo interno de Customer Intelligence (fase CI-02).
 *
 * Tabela principal de eventos comportamentais. Tratada como append-only: um
 * evento e um fato ocorrido, nunca editado. Por isso existe apenas `created_at`
 * (quando a linha foi gravada) e nao `updated_at`.
 *
 * `occurred_at` e o instante do fato de negocio e pode divergir de `created_at`
 * quando a gravacao passar pela fila (CI-05).
 *
 * Retencao: 180 dias de evento bruto — politica documentada em
 * docs/CUSTOMER_INTELLIGENCE_INTERNAL.md. A rotina de expurgo NAO faz parte
 * desta fase.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ci_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('event_uuid')->unique();

            // Todos nulos: um evento pode existir sem visitante resolvido
            // (ex.: gerado por job de sistema) sem ser descartado.
            $table->foreignId('visitor_id')->nullable()->constrained('ci_visitors')->nullOnDelete();
            $table->foreignId('session_id')->nullable()->constrained('ci_sessions')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('event_name', 64);
            $table->string('event_category', 32)->nullable();

            // Referencia polimorfica a entidade do dominio (Product, Order...).
            // E o que permite perguntar "quantas visualizacoes este produto teve"
            // sem depender de vasculhar o JSON de properties.
            $table->string('entity_type', 64)->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();

            $table->json('properties')->nullable();

            $table->timestamp('occurred_at');
            $table->timestamp('created_at')->nullable();

            // Consulta por tipo de evento em um periodo (dashboard).
            $table->index(['event_name', 'occurred_at']);
            // Recortes por periodo e expurgo por retencao.
            $table->index('occurred_at');
            // Historico de uma entidade especifica.
            $table->index(['entity_type', 'entity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ci_events');
    }
};
