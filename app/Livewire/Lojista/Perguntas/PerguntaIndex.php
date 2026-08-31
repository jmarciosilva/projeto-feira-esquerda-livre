<?php

namespace App\Livewire\Lojista\Perguntas;

use App\Models\ProductQuestion;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * As perguntas dirigidas às ofertas desta loja.
 *
 * ## O que a CAT-DOM-02F corrigiu
 *
 * Até aqui a autorização perguntava *"tenho alguma oferta neste produto?"* —
 * `whereHas('product.offers', ...)`. Era indistinguível do certo enquanto
 * `Product` e `ProductOffer` andavam 1:1, e vira defeito no instante em que dois
 * vendedores oferecem o mesmo item: B poderia responder, assinando com a loja
 * dele, a pergunta que o cliente fez a A.
 *
 * A pergunta certa é *"esta pergunta é da minha oferta?"*, e ela vive em
 * `ProductQuestion::podeSerRespondidaPor()` / `scopeDirigidaAoExpositor()`.
 *
 * Cada método público de um componente Livewire é um endpoint próprio,
 * alcançável sem passar pela tela que o renderizou — por isso a consulta
 * escopada é refeita **em cada ação**, e não herdada do estado hidratado.
 */
class PerguntaIndex extends Component
{
    use WithPagination;

    public string $filter = 'pending';

    /** Respostas sendo redigidas, indexadas por question id. */
    public array $answers = [];

    public function updatingFilter(): void
    {
        $this->resetPage();
    }

    public function saveAnswer(int $questionId): void
    {
        $expositorId = auth()->user()->expositor?->id;

        $question = ProductQuestion::dirigidaAoExpositor($expositorId)->findOrFail($questionId);

        $answer = trim($this->answers[$questionId] ?? '');

        $this->validateOnly("answers.{$questionId}", [
            "answers.{$questionId}" => 'required|string|min:2|max:2000',
        ]);

        $question->update([
            'answer' => $answer,
            'answered_at' => now(),
            'answered_by' => auth()->id(),
        ]);

        unset($this->answers[$questionId]);
        session()->flash('success', 'Resposta publicada com sucesso!');
    }

    public function toggleVisibility(int $questionId): void
    {
        $expositorId = auth()->user()->expositor?->id;

        $question = ProductQuestion::dirigidaAoExpositor($expositorId)->findOrFail($questionId);

        // Alterna a visibilidade pública da pergunta na página da oferta. É o
        // poder que o lojista sempre teve, e não foi ampliado: ele não apaga a
        // pergunta, não edita o texto do cliente e não alcança a moderação
        // global, que é da curadoria.
        $question->update(['is_visible' => ! $question->is_visible]);
    }

    public function render(): View
    {
        $expositorId = auth()->user()->expositor?->id;

        $questions = ProductQuestion::dirigidaAoExpositor($expositorId)
            ->when($this->filter === 'pending', fn ($q) => $q->whereNull('answered_at'))
            ->when($this->filter === 'answered', fn ($q) => $q->whereNotNull('answered_at'))
            ->with(['product', 'user'])
            ->orderByDesc('created_at')
            ->paginate(15);

        $pendingCount = ProductQuestion::dirigidaAoExpositor($expositorId)->whereNull('answered_at')->count();
        $answeredCount = ProductQuestion::dirigidaAoExpositor($expositorId)->whereNotNull('answered_at')->count();

        return view('livewire.lojista.perguntas.pergunta-index', compact('questions', 'pendingCount', 'answeredCount'))
            ->layout('lojista.layouts.app', ['title' => 'Perguntas dos Clientes']);
    }
}
