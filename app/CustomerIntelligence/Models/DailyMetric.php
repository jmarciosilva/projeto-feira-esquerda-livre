<?php

namespace App\CustomerIntelligence\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Agregado diario pre-calculado.
 *
 * Existe para que o painel nao precise varrer `ci_events` e para que o evento
 * bruto possa ser expurgado apos 180 dias sem apagar a serie historica.
 *
 * Alimentada de forma incremental a cada evento gravado, e reconstruivel pelo
 * comando customer-intelligence:rebuild-daily-metrics.
 */
class DailyMetric extends Model
{
    protected $table = 'ci_daily_metrics';

    protected $fillable = [
        'metric_date',
        'metric_name',
        'dimension_type',
        'dimension_value',
        'metric_value',
    ];

    protected function casts(): array
    {
        return [
            'metric_date' => 'date',
            'metric_value' => 'decimal:4',
        ];
    }

    /**
     * Grava ou atualiza o valor de uma metrica do dia.
     *
     * `dimension_type` e `dimension_value` usam '' para "sem dimensao" porque a
     * chave unica da tabela nao funcionaria com NULL no MySQL.
     *
     * Grava pelo query builder, e nao por `updateOrCreate`, pelo mesmo motivo
     * que IncrementDailyMetric e o comando de reconstrucao: o cast `date` do
     * Eloquent reformata a data na escrita usando o formato do grammar
     * (`Y-m-d H:i:s`), independentemente do que se passe na entrada. O valor
     * gravado divergiria do valor usado na busca — o MySQL disfarca porque a
     * coluna e DATE e normaliza, mas o SQLite guarda os dois formatos e o
     * `updateOrCreate` perderia a semantica de update.
     *
     * Passando pelo query builder, `metric_date` sai daqui canonico (`Y-m-d`)
     * nos dois bancos, exatamente como nos outros caminhos de escrita.
     */
    public static function record(
        string $date,
        string $name,
        float $value,
        string $dimensionType = '',
        string $dimensionValue = '',
    ): self {
        $chaves = [
            'metric_date' => Carbon::parse($date)->toDateString(),
            'metric_name' => $name,
            'dimension_type' => $dimensionType,
            'dimension_value' => $dimensionValue,
        ];

        $agora = Carbon::now();

        DB::table((new static)->getTable())->updateOrInsert(
            $chaves,
            fn (bool $existe) => $existe
                ? ['metric_value' => $value, 'updated_at' => $agora]
                : ['metric_value' => $value, 'created_at' => $agora, 'updated_at' => $agora],
        );

        return static::where($chaves)->firstOrFail();
    }
}
