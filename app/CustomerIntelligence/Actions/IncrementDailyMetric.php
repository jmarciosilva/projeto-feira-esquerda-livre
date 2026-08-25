<?php

namespace App\CustomerIntelligence\Actions;

use App\CustomerIntelligence\Enums\MetricName;
use DateTimeInterface;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Soma um valor a uma metrica diaria de `ci_daily_metrics`.
 *
 * Concorrencia: dois jobs podem incrementar a mesma metrica ao mesmo tempo. Ler
 * o valor em PHP, somar e gravar perderia incrementos — por isso a soma acontece
 * no banco, num unico `UPDATE ... SET metric_value = metric_value + ?`, que e
 * atomico tanto no MySQL quanto no SQLite.
 *
 * A linha pode nao existir ainda. Nesse caso tentamos inserir; se outra conexao
 * inserir primeiro, a chave unica da tabela rejeita a segunda tentativa e nos
 * caimos de volta no incremento. Nada se perde e nada duplica.
 *
 * Nao ha branch por dialeto de banco: `increment()` do query builder resolve
 * igual nos dois.
 */
class IncrementDailyMetric
{
    public function __invoke(
        MetricName $metric,
        DateTimeInterface|string|null $date = null,
        float $delta = 1,
        string $dimensionType = '',
        string $dimensionValue = '',
    ): void {
        $keys = [
            'metric_date' => $this->day($date),
            'metric_name' => $metric->value,
            // '' e nao null: no MySQL valores NULL sao distintos entre si em
            // indice UNIQUE, e a chave da tabela deixaria de cumprir seu papel.
            'dimension_type' => $dimensionType,
            'dimension_value' => $dimensionValue,
        ];

        if ($this->add($keys, $delta) > 0) {
            return;
        }

        try {
            DB::table('ci_daily_metrics')->insert($keys + [
                'metric_value' => $delta,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
        } catch (UniqueConstraintViolationException) {
            // Outra conexao criou a linha entre o UPDATE e o INSERT.
            $this->add($keys, $delta);
        }
    }

    /**
     * @param  array<string, string>  $keys
     */
    private function add(array $keys, float $delta): int
    {
        return DB::table('ci_daily_metrics')
            ->where($keys)
            ->increment('metric_value', $delta, ['updated_at' => Carbon::now()]);
    }

    private function day(DateTimeInterface|string|null $date): string
    {
        return match (true) {
            $date === null => Carbon::now()->toDateString(),
            $date instanceof DateTimeInterface => Carbon::instance($date)->toDateString(),
            default => Carbon::parse($date)->toDateString(),
        };
    }
}
