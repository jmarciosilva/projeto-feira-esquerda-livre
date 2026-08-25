<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Modulo interno de Customer Intelligence (fase CI-02).
 *
 * Agregado diario pre-calculado. E o que torna a retencao curta de `ci_events`
 * viavel: o painel le daqui, entao o evento bruto pode ser expurgado sem perder
 * a serie historica. Retencao permanente.
 *
 * Os agregadores em si NAO fazem parte desta fase — aqui existe apenas a
 * fundacao da tabela.
 *
 * Nota sobre `dimension_type` / `dimension_value`: sao NOT NULL com default ''
 * de proposito. No MySQL, valores NULL sao considerados distintos entre si em
 * indice UNIQUE, entao colunas nulaveis permitiriam gravar a mesma metrica
 * global varias vezes. O '' representa "sem dimensao" e faz a chave unica
 * cumprir seu papel.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ci_daily_metrics', function (Blueprint $table) {
            $table->id();

            $table->date('metric_date');
            $table->string('metric_name', 64);

            // Recorte opcional da metrica (ex.: 'expositor' + '14').
            $table->string('dimension_type', 32)->default('');
            $table->string('dimension_value', 128)->default('');

            $table->decimal('metric_value', 20, 4)->default(0);

            $table->timestamps();

            $table->unique(
                ['metric_date', 'metric_name', 'dimension_type', 'dimension_value'],
                'ci_daily_metrics_unique'
            );
            // Serie temporal de uma metrica.
            $table->index(['metric_name', 'metric_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ci_daily_metrics');
    }
};
