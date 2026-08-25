<?php

namespace App\CustomerIntelligence\Queries;

use App\CustomerIntelligence\Enums\MetricName;
use App\CustomerIntelligence\Models\DailyMetric;
use App\CustomerIntelligence\Models\TrackedEvent;
use App\CustomerIntelligence\Models\Visitor;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Dados do dashboard, vindos exclusivamente do banco local.
 *
 * Os quatro cartoes e o grafico saem de `ci_daily_metrics`, nao de `ci_events`:
 * agregar a tabela de eventos inteira a cada carregamento seria uma varredura
 * que cresce sem limite. As listas de "recentes" leem `ci_events` porque sao
 * consultas curtas, com LIMIT e ordenacao por indice.
 */
class DashboardQuery
{
    /**
     * Totais do periodo mais a serie diaria para o grafico.
     *
     * @return array{events:int, visitors:int, sessions:int, conversions:int, trend:array<string,int>}
     */
    public function metrics(Carbon $from, Carbon $to): array
    {
        $totais = DailyMetric::query()
            ->whereBetween('metric_date', [$from->toDateString(), $to->toDateString()])
            ->where('dimension_type', '')
            ->groupBy('metric_name')
            ->pluck(DB::raw('SUM(metric_value)'), 'metric_name');

        return [
            'events' => (int) ($totais[MetricName::Eventos->value] ?? 0),
            'visitors' => (int) ($totais[MetricName::Visitantes->value] ?? 0),
            'sessions' => (int) ($totais[MetricName::Sessoes->value] ?? 0),
            'conversions' => (int) ($totais[MetricName::Conversoes->value] ?? 0),
            'trend' => $this->trend($from, $to),
        ];
    }

    /**
     * Serie diaria de eventos, no formato que o grafico consome: data => total.
     *
     * @return array<string, int>
     */
    public function trend(Carbon $from, Carbon $to): array
    {
        return DailyMetric::query()
            ->where('metric_name', MetricName::Eventos->value)
            ->where('dimension_type', '')
            ->whereBetween('metric_date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('metric_date')
            ->get(['metric_date', 'metric_value'])
            ->mapWithKeys(fn (DailyMetric $m) => [
                $m->metric_date->format('d/m') => (int) $m->metric_value,
            ])
            ->all();
    }

    /**
     * Total por tipo de evento no periodo — desdobramento que a dimensao
     * `event_name` de `ci_daily_metrics` ja guarda pronto.
     *
     * @return array<string, int>
     */
    public function byEventName(Carbon $from, Carbon $to): array
    {
        return DailyMetric::query()
            ->where('metric_name', MetricName::Eventos->value)
            ->where('dimension_type', MetricName::DIMENSION_EVENT_NAME)
            ->whereBetween('metric_date', [$from->toDateString(), $to->toDateString()])
            ->groupBy('dimension_value')
            ->pluck(DB::raw('SUM(metric_value)'), 'dimension_value')
            ->map(fn ($v) => (int) $v)
            ->all();
    }

    /**
     * Visitantes vistos mais recentemente no periodo.
     *
     * @return list<array<string, mixed>>
     */
    public function recentVisitors(Carbon $from, Carbon $to, int $limit = 5): array
    {
        return Visitor::query()
            ->with('user:id,name,email')
            ->withCount(['events as events_count' => fn ($q) => $q->whereBetween('occurred_at', [$from, $to])])
            ->whereBetween('last_seen_at', [$from, $to])
            ->orderByDesc('last_seen_at')
            ->limit($limit)
            ->get()
            ->map(fn (Visitor $v) => VisitorQuery::present($v))
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function recentEvents(Carbon $from, Carbon $to, int $limit = 10): array
    {
        return TrackedEvent::query()
            ->with(['visitor:id,visitor_uuid,user_id', 'visitor.user:id,email', 'user:id,email'])
            ->whereBetween('occurred_at', [$from, $to])
            ->orderByDesc('occurred_at')
            ->limit($limit)
            ->get()
            ->map(fn (TrackedEvent $e) => EventQuery::present($e))
            ->all();
    }
}
