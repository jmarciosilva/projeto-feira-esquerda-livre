<?php

namespace App\Livewire\Admin\CustomerIntelligence;

use App\CustomerIntelligence\Queries\DashboardQuery;
use Illuminate\Support\Carbon;
use Livewire\Component;

/**
 * Dashboard do modulo interno.
 *
 * Le exclusivamente o banco local: `ci_daily_metrics` para os cartoes e o
 * grafico, `ci_events`/`ci_visitors` para as listas de recentes. Nenhuma
 * chamada HTTP.
 */
class Dashboard extends Component
{
    public string $period = '7';

    public function setPeriod(string $period): void
    {
        $this->period = $period;
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function range(): array
    {
        $to = Carbon::now()->endOfDay();

        $from = match ($this->period) {
            'today' => Carbon::now()->startOfDay(),
            '30' => Carbon::now()->subDays(30)->startOfDay(),
            '90' => Carbon::now()->subDays(90)->startOfDay(),
            default => Carbon::now()->subDays(7)->startOfDay(),
        };

        return [$from, $to];
    }

    public function render()
    {
        [$from, $to] = $this->range();
        $query = app(DashboardQuery::class);

        return view('plugins.jmf-ci.dashboard', [
            'metrics' => $query->metrics($from, $to),
            'recentContacts' => $query->recentVisitors($from, $to),
            'recentEvents' => $query->recentEvents($from, $to),
            'dateRange' => ['start_date' => $from->toDateString(), 'end_date' => $to->toDateString()],
        ]);
    }
}
