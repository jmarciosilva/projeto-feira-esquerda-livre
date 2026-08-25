<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Indice da timeline do visitante (fase CI-06).
 *
 * A tela de detalhe consulta:
 *
 *   SELECT * FROM ci_events WHERE visitor_id = ? ORDER BY occurred_at DESC
 *
 * A chave estrangeira ja cobria o filtro por `visitor_id`, mas a ordenacao caia
 * em filesort. O indice composto resolve filtro e ordenacao de uma vez.
 *
 * Como ele comeca por `visitor_id`, tambem satisfaz a exigencia de indice da
 * foreign key — nenhum indice fica redundante.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ci_events', function (Blueprint $table) {
            $table->index(['visitor_id', 'occurred_at'], 'ci_events_visitor_timeline_index');
        });
    }

    public function down(): void
    {
        Schema::table('ci_events', function (Blueprint $table) {
            $table->dropIndex('ci_events_visitor_timeline_index');
        });
    }
};
