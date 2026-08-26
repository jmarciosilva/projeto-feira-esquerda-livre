<?php

namespace App\Livewire\Admin\CustomerIntelligence;

use App\CustomerIntelligence\Actions\RecordAuditLog;
use App\CustomerIntelligence\Enums\AuditAction;
use Livewire\Component;

class DashboardShow extends Component
{
    /**
     * A auditoria mora no `mount()`, e nao no `render()`.
     *
     * `render()` reexecuta a cada hidratacao e a cada interacao — filtrar,
     * paginar, trocar periodo. Auditar ali transformaria uma visita em dezenas
     * de linhas e tornaria a trilha ilegivel justamente quando ela precisasse
     * ser lida. `mount()` roda uma vez por abertura de tela, que e exatamente
     * o fato que se quer registrar.
     */
    public function mount(RecordAuditLog $auditar): void
    {
        $this->authorize('customer_intelligence.visualizar');

        $auditar(AuditAction::DashboardView);
    }

    public function render()
    {
        return view('livewire.admin.customer-intelligence.dashboard-show')
            ->layout('admin.layouts.app', ['title' => 'Inteligência de Cliente']);
    }
}
