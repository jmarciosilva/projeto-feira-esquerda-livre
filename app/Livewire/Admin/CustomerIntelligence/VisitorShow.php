<?php

namespace App\Livewire\Admin\CustomerIntelligence;

use App\CustomerIntelligence\Actions\RecordAuditLog;
use App\CustomerIntelligence\Enums\AuditAction;
use App\CustomerIntelligence\Models\Visitor;
use App\CustomerIntelligence\Queries\EventQuery;
use App\CustomerIntelligence\Queries\VisitorQuery;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Detalhe de um visitante: identificacao, primeira e ultima visita, contagem de
 * sessoes e a timeline de eventos.
 */
class VisitorShow extends Component
{
    use WithPagination;

    public string $visitorUuid = '';

    private const PER_PAGE = 25;

    public function mount(string $visitor, RecordAuditLog $auditar): void
    {
        $this->authorize('customer_intelligence.visualizar');

        $this->visitorUuid = $visitor;

        // A chave interna, e nao o `visitor_uuid`: ela identifica o recurso
        // consultado sem replicar na auditoria o identificador publico do
        // visitante. Consulta escalar e indexada, um unico valor de volta.
        //
        // Visitante inexistente nao gera linha: o `render()` responde 404 e
        // nao houve acesso a dado nenhum para registrar.
        $id = Visitor::where('visitor_uuid', $visitor)->value('id');

        if ($id !== null) {
            $auditar(AuditAction::VisitorView, 'ci_visitor', $id);
        }
    }

    public function render()
    {
        $visitor = app(VisitorQuery::class)->find($this->visitorUuid);

        if ($visitor === null) {
            throw new NotFoundHttpException('Visitante não encontrado.');
        }

        $paginator = app(EventQuery::class)->forVisitor($visitor->id, self::PER_PAGE);

        return view('plugins.jmf-ci.contacts.show', [
            'contact' => VisitorQuery::present($visitor),
            'events' => $paginator->getCollection()->map(fn ($e) => EventQuery::present($e))->all(),
            'total' => $paginator->total(),
            'currentPage' => $paginator->currentPage(),
            'perPage' => self::PER_PAGE,
        ])->layout('admin.layouts.app', ['title' => 'Visitante — Inteligência de Cliente']);
    }
}
