<?php

namespace App\CustomerIntelligence\Console;

use App\CustomerIntelligence\Enums\MetricName;
use App\CustomerIntelligence\Models\DailyMetric;
use App\CustomerIntelligence\Models\TrackedEvent;
use App\CustomerIntelligence\Models\VisitorSession;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Reconstroi `ci_daily_metrics` a partir de `ci_events` e `ci_sessions`.
 *
 * A agregacao normal e incremental, feita quando o evento e gravado. Este
 * comando existe para os casos em que o incremento nao aconteceu: uma falha de
 * job, uma importacao, ou uma correcao de metrica.
 *
 * Idempotente: apaga os agregados do intervalo antes de recalcular, entao rodar
 * duas vezes produz o mesmo resultado. So toca `ci_daily_metrics` — os eventos
 * brutos nunca sao alterados.
 *
 * Convivencia com a retencao: eventos com mais de 180 dias sao expurgados, mas
 * seus agregados sao permanentes. Reconstruir um intervalo cujo evento bruto ja
 * nao existe zeraria a serie historica, entao o comando nunca vai antes do
 * evento mais antigo ainda disponivel — um `--from` anterior a ele e ajustado,
 * com aviso.
 *
 * Sem nenhum evento em `ci_events`, o comando nao apaga nada: preserva os
 * agregados e informa. `ci_sessions` nao serve como fonte substituta para
 * decidir o que apagar, justamente porque sessoes nao sao expurgadas junto com
 * os eventos e sobreviveriam a eles.
 */
class RebuildDailyMetricsCommand extends Command
{
    protected $signature = 'customer-intelligence:rebuild-daily-metrics
                            {--from= : Data inicial (Y-m-d). Padrão: o dia do evento mais antigo}
                            {--to= : Data final (Y-m-d). Padrão: hoje}';

    protected $description = 'Recalcula os agregados diários do Customer Intelligence a partir dos eventos';

    public function handle(): int
    {
        [$from, $to] = $this->range();

        if ($from === null) {
            $this->components->info(
                'Não há eventos brutos para reconstruir. Os agregados existentes foram preservados.'
            );

            return self::SUCCESS;
        }

        $this->components->info(sprintf(
            'Reconstruindo agregados de %s a %s.',
            $from->toDateString(),
            $to->toDateString()
        ));

        DB::transaction(function () use ($from, $to) {
            // Limpa antes de recalcular: e o que torna a reexecucao segura.
            DailyMetric::whereBetween('metric_date', [$from->toDateString(), $to->toDateString()])->delete();

            $linhas = array_merge(
                $this->eventTotals($from, $to),
                $this->eventTotalsByName($from, $to),
                $this->conversions($from, $to),
                $this->sessionTotals($from, $to),
                $this->visitorTotals($from, $to),
            );

            foreach (array_chunk($linhas, 500) as $lote) {
                DailyMetric::insert($lote);
            }

            $this->components->info(count($linhas).' agregados gravados.');
        });

        return self::SUCCESS;
    }

    /**
     * @return array{0: ?Carbon, 1: Carbon}
     */
    private function range(): array
    {
        $to = $this->option('to') ? Carbon::parse($this->option('to')) : Carbon::now();

        // A fonte da verdade e `ci_events`, e so ela. Usar `ci_sessions` como
        // substituto faria o comando apagar agregados de dias cujos eventos
        // brutos ja foram expurgados — e nao havia como reconstrui-los.
        $bruto = TrackedEvent::min('occurred_at');
        $maisAntigo = $bruto ? Carbon::parse($bruto)->startOfDay() : null;

        if (! $this->option('from')) {
            return [$maisAntigo, $to];
        }

        $from = Carbon::parse($this->option('from'));

        // Antes do evento mais antigo nao ha o que recalcular: apagar os
        // agregados desse trecho destruiria historico que a retencao preservou.
        if ($maisAntigo !== null && $from->lt($maisAntigo)) {
            $this->components->warn(sprintf(
                'Início ajustado de %s para %s: não há eventos brutos anteriores, e os agregados desse período são preservados.',
                $from->toDateString(),
                $maisAntigo->toDateString()
            ));

            return [$maisAntigo, $to];
        }

        return [$from, $to];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function eventTotals(Carbon $from, Carbon $to): array
    {
        $rows = TrackedEvent::query()
            ->whereBetween('occurred_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->groupBy('dia')
            ->pluck(DB::raw('COUNT(*)'), DB::raw('DATE(occurred_at) as dia'));

        return $this->toRows(MetricName::Eventos, $rows);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function eventTotalsByName(Carbon $from, Carbon $to): array
    {
        $linhas = [];

        $rows = TrackedEvent::query()
            ->whereBetween('occurred_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->selectRaw('DATE(occurred_at) as dia, event_name, COUNT(*) as total')
            ->groupBy('dia', 'event_name')
            ->get();

        foreach ($rows as $row) {
            $linhas[] = $this->row(
                MetricName::Eventos,
                $row->dia,
                (int) $row->total,
                MetricName::DIMENSION_EVENT_NAME,
                $row->event_name instanceof \BackedEnum ? $row->event_name->value : (string) $row->event_name,
            );
        }

        return $linhas;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function conversions(Carbon $from, Carbon $to): array
    {
        $rows = TrackedEvent::query()
            ->where('event_name', MetricName::conversionEvent()->value)
            ->whereBetween('occurred_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->groupBy('dia')
            ->pluck(DB::raw('COUNT(*)'), DB::raw('DATE(occurred_at) as dia'));

        return $this->toRows(MetricName::Conversoes, $rows);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function sessionTotals(Carbon $from, Carbon $to): array
    {
        $rows = VisitorSession::query()
            ->whereBetween('started_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->groupBy('dia')
            ->pluck(DB::raw('COUNT(*)'), DB::raw('DATE(started_at) as dia'));

        return $this->toRows(MetricName::Sessoes, $rows);
    }

    /**
     * Visitantes distintos por dia — a metrica que nao e aditiva e por isso
     * precisa de COUNT(DISTINCT) na reconstrucao.
     *
     * @return list<array<string, mixed>>
     */
    private function visitorTotals(Carbon $from, Carbon $to): array
    {
        $rows = VisitorSession::query()
            ->whereBetween('started_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
            ->groupBy('dia')
            ->pluck(DB::raw('COUNT(DISTINCT visitor_id)'), DB::raw('DATE(started_at) as dia'));

        return $this->toRows(MetricName::Visitantes, $rows);
    }

    /**
     * @param  Collection<string, int>  $rows
     * @return list<array<string, mixed>>
     */
    private function toRows(MetricName $metric, $rows): array
    {
        $linhas = [];

        foreach ($rows as $dia => $total) {
            $linhas[] = $this->row($metric, (string) $dia, (int) $total);
        }

        return $linhas;
    }

    /**
     * @return array<string, mixed>
     */
    private function row(
        MetricName $metric,
        string $dia,
        int $total,
        string $dimensionType = '',
        string $dimensionValue = '',
    ): array {
        return [
            'metric_date' => $dia,
            'metric_name' => $metric->value,
            'dimension_type' => $dimensionType,
            'dimension_value' => $dimensionValue,
            'metric_value' => $total,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
