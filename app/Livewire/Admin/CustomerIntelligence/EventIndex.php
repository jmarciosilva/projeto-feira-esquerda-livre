<?php

namespace App\Livewire\Admin\CustomerIntelligence;

use App\CustomerIntelligence\Actions\RecordAuditLog;
use App\CustomerIntelligence\Enums\AuditAction;
use App\CustomerIntelligence\Queries\EventQuery;
use Illuminate\Support\Carbon;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Listagem de eventos do painel, lendo `ci_events`.
 *
 * Filtros e paginacao sao resolvidos pelo banco. Nenhuma chamada HTTP.
 */
class EventIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public string $eventName = '';

    public string $period = '30';

    private const PER_PAGE = 50;

    /**
     * Uma linha por abertura de tela. Filtrar e paginar passam por
     * `updating*`/`render`, nunca por `mount()` — a trilha registra o acesso,
     * nao cada movimento dentro dele.
     */
    public function mount(RecordAuditLog $auditar): void
    {
        // A tela sempre foi embutida no painel, que ja autoriza. A checagem
        // propria entrou junto com a auditoria: agora montar o componente
        // GRAVA — e um registro so vale se corresponder a um acesso legitimo.
        $this->authorize('customer_intelligence.visualizar');

        $auditar(AuditAction::EventsView);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingEventName(): void
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

        $paginator = app(EventQuery::class)->paginate($from, $to, [
            'search' => $this->search ?: null,
            'event_name' => $this->eventName ?: null,
        ], self::PER_PAGE);

        return view('plugins.jmf-ci.events.index', [
            'events' => $paginator->getCollection()->map(fn ($e) => EventQuery::present($e))->all(),
            'total' => $paginator->total(),
            'currentPage' => $paginator->currentPage(),
            'perPage' => self::PER_PAGE,
        ]);
    }
}
