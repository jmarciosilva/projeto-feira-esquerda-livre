<?php

namespace App\Livewire\Admin\CustomerIntelligence;

use App\CustomerIntelligence\Queries\EventQuery;
use App\CustomerIntelligence\Queries\VisitorQuery;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Detalhe de um visitante: identificacao, primeira e ultima visita, contagem de
 * sessoes e a timeline de eventos.
 *
 * Ate a CI-05 esta tela existia como view mas nao era alcancavel — as rotas do
 * plugin do SDK nunca chegaram a ser registradas. Agora tem rota propria.
 */
class VisitorShow extends Component
{
    use WithPagination;

    public string $visitorUuid = '';

    private const PER_PAGE = 25;

    public function mount(string $visitor): void
    {
        $this->authorize('customer_intelligence.visualizar');

        $this->visitorUuid = $visitor;
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
