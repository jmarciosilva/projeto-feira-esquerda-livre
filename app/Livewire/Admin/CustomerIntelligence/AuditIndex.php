<?php

namespace App\Livewire\Admin\CustomerIntelligence;

use App\CustomerIntelligence\Actions\RecordAuditLog;
use App\CustomerIntelligence\Enums\AuditAction;
use App\CustomerIntelligence\Queries\AuditLogQuery;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Tela de auditoria administrativa do Customer Intelligence.
 *
 * Permissao propria, `customer_intelligence.auditoria`, e nao a de visualizar
 * o painel: quem pode ver metricas nao passa por isso a ver quem olhou o que.
 * A rota tambem exige a permissao, entao a autorizacao existe nos dois niveis —
 * a checagem no `mount()` protege o componente de ser montado por outro
 * caminho que nao a rota.
 *
 * Somente leitura. Nao ha acao de editar, apagar ou exportar: uma trilha de
 * auditoria com CRUD nao seria trilha de auditoria.
 */
class AuditIndex extends Component
{
    use WithPagination;

    public string $action = '';

    private const PER_PAGE = 50;

    /**
     * Abrir a auditoria e, tambem, um acesso a dado sensivel — e por isso fica
     * registrado na propria auditoria. Nao ha recursao: a gravacao acontece uma
     * vez, no `mount()`, e a leitura seguinte apenas exibe o que ja existe.
     */
    public function mount(RecordAuditLog $auditar): void
    {
        $this->authorize('customer_intelligence.auditoria');

        $auditar(AuditAction::AuditView);
    }

    public function updatingAction(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $filtro = AuditAction::tryFrom($this->action);

        return view('livewire.admin.customer-intelligence.audit-index', [
            'registros' => app(AuditLogQuery::class)->paginate($filtro, self::PER_PAGE),
            'acoes' => AuditAction::all(),
            'retencaoDias' => (int) config('customer-intelligence-internal.retention.audit_days', 730),
        ])->layout('admin.layouts.app', ['title' => 'Auditoria — Inteligência de Cliente']);
    }
}
