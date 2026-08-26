<?php

namespace App\Livewire\Admin\CustomerIntelligence;

use App\CustomerIntelligence\Actions\RecordAuditLog;
use App\CustomerIntelligence\Enums\AuditAction;
use App\CustomerIntelligence\Queries\VisitorQuery;
use Illuminate\Support\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Listagem de visitantes — a tela que o painel apresenta como "Contatos".
 *
 * Le `ci_visitors`, trazendo nome e e-mail por relacao com `users` quando o
 * visitante ja se autenticou. Nenhuma chamada HTTP.
 */
class VisitorIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public string $period = '30';

    private const PER_PAGE = 25;

    public function mount(RecordAuditLog $auditar): void
    {
        $this->authorize('customer_intelligence.visualizar');

        $auditar(AuditAction::VisitorsView);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingPeriod(): void
    {
        $this->resetPage();
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function range(): array
    {
        $to = Carbon::now()->endOfDay();

        $from = match ($this->period) {
            'today' => Carbon::now()->startOfDay(),
            '7' => Carbon::now()->subDays(7)->startOfDay(),
            '90' => Carbon::now()->subDays(90)->startOfDay(),
            default => Carbon::now()->subDays(30)->startOfDay(),
        };

        return [$from, $to];
    }

    public function render()
    {
        [$from, $to] = $this->range();

        $paginator = app(VisitorQuery::class)->paginate($from, $to, [
            'search' => $this->search ?: null,
        ], self::PER_PAGE);

        return view('plugins.jmf-ci.contacts.index', [
            'contacts' => $paginator->getCollection()->map(fn ($v) => VisitorQuery::present($v))->all(),
            'total' => $paginator->total(),
            'currentPage' => $paginator->currentPage(),
            'perPage' => self::PER_PAGE,
        ]);
    }
}
