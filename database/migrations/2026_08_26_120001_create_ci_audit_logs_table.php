<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Trilha de auditoria administrativa do Customer Intelligence (GOV-01C).
 *
 * Registra quem acessou os dados comportamentais e quem executou as operacoes
 * sensiveis do modulo. Append-only: nao ha CRUD administrativo e nao existe
 * `updated_at` — uma linha de auditoria alteravel nao e auditoria.
 *
 * O que esta tabela NAO tem, por decisao e nao por esquecimento:
 *
 *   sem IP, user-agent ou cookie   nao e a origem tecnica do acesso que
 *                                  interessa, e sim quem respondeu por ele;
 *   sem coluna de metadata livre   e por campos livres que dado pessoal entra
 *                                  sem ninguem ter decidido coleta-lo. Se
 *                                  algum caso precisar de contexto, ele vira
 *                                  coluna tipada, justificada e documentada.
 *
 * `resource_type`/`resource_id` sao tipados justamente para dar o "sobre o que"
 * sem abrir a porta do campo livre. `user_id` e nulo nas execucoes agendadas,
 * que nao tem gente por tras.
 *
 * Retencao: 730 dias, com comando e agendamento proprios
 * (customer-intelligence:prune-audit-logs). Nao compartilha nada com o expurgo
 * de `ci_events` — prazos diferentes, naturezas diferentes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ci_audit_logs', function (Blueprint $table) {
            $table->id();

            // Nulo em execucao agendada; nulo tambem se a conta for removida
            // depois — a acao continua registrada mesmo sem o ator.
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('action', 64);

            $table->string('resource_type', 64)->nullable();
            $table->string('resource_id', 64)->nullable();

            $table->timestamp('created_at')->nullable();

            // Listagem da tela, sempre em ordem cronologica decrescente.
            $table->index('created_at');
            // Recorte por tipo de acao e por responsavel.
            $table->index(['action', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ci_audit_logs');
    }
};
