<?php

namespace App\CustomerIntelligence\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Agregado diario pre-calculado.
 *
 * Existe para que o painel nao precise varrer `ci_events` e para que o evento
 * bruto possa ser expurgado apos 180 dias sem apagar a serie historica.
 *
 * Os agregadores que alimentam esta tabela ainda nao existem — sao da CI-07.
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
     */
    public static function record(
        string $date,
        string $name,
        float $value,
        string $dimensionType = '',
        string $dimensionValue = '',
    ): self {
        return static::updateOrCreate(
            [
                'metric_date' => $date,
                'metric_name' => $name,
                'dimension_type' => $dimensionType,
                'dimension_value' => $dimensionValue,
            ],
            ['metric_value' => $value],
        );
    }
}
