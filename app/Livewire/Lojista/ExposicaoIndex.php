<?php

namespace App\Livewire\Lojista;

use App\Models\Expositor;
use App\Models\ExpositorImpression;
use Illuminate\View\View;
use Livewire\Component;

class ExposicaoIndex extends Component
{
    public function render(): View
    {
        $user      = auth()->user();
        $expositor = Expositor::where('user_id', $user->id)->first();

        if (! $expositor || ! $expositor->is_featured) {
            return view('livewire.lojista.exposicao-index', [
                'expositor'  => $expositor,
                'onHome'     => false,
                'stats'      => null,
                'chartData'  => [],
                'activeSlot' => null,
            ])->layout('lojista.layouts.app', ['title' => 'Exposição na Home']);
        }

        $stats = [
            'total'   => $expositor->total_impressions,
            'last7'   => $expositor->impressionsLastDays(7),
            'last30'  => $expositor->impressionsLastDays(30),
        ];

        // Dados para o gráfico (últimos 30 dias, agrupados por dia)
        $chartData = ExpositorImpression::where('expositor_id', $expositor->id)
            ->where('rendered_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(rendered_at) as day, COUNT(*) as count')
            ->groupBy('day')
            ->orderBy('day')
            ->get()
            ->map(fn ($r) => ['day' => $r->day, 'count' => $r->count])
            ->toArray();

        // Top-10 dias
        $topDays = ExpositorImpression::where('expositor_id', $expositor->id)
            ->selectRaw('DATE(rendered_at) as day, COUNT(*) as count')
            ->groupBy('day')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        $activeSlot = $expositor->activeSlot();

        return view('livewire.lojista.exposicao-index', [
            'expositor'  => $expositor,
            'onHome'     => true,
            'stats'      => $stats,
            'chartData'  => $chartData,
            'topDays'    => $topDays,
            'activeSlot' => $activeSlot,
        ])->layout('lojista.layouts.app', ['title' => 'Exposição na Home']);
    }
}
