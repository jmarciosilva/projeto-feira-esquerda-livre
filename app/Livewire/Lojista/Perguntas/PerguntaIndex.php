<?php

namespace App\Livewire\Lojista\Perguntas;

use App\Models\ProductQuestion;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

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

        $question = ProductQuestion::whereHas(
            'product',
            fn ($q) => $q->where('expositor_id', $expositorId)
        )->findOrFail($questionId);

        $answer = trim($this->answers[$questionId] ?? '');

        $this->validateOnly("answers.{$questionId}", [
            "answers.{$questionId}" => 'required|string|min:2|max:2000',
        ]);

        $question->update([
            'answer'      => $answer,
            'answered_at' => now(),
            'answered_by' => auth()->id(),
        ]);

        unset($this->answers[$questionId]);
        session()->flash('success', 'Resposta publicada com sucesso!');
    }

    public function toggleVisibility(int $questionId): void
    {
        $expositorId = auth()->user()->expositor?->id;

        $question = ProductQuestion::whereHas(
            'product',
            fn ($q) => $q->where('expositor_id', $expositorId)
        )->findOrFail($questionId);

        $question->update(['is_visible' => ! $question->is_visible]);
    }

    public function render(): View
    {
        $expositorId = auth()->user()->expositor?->id;

        $questions = ProductQuestion::whereHas(
            'product',
            fn ($q) => $q->where('expositor_id', $expositorId)
        )
            ->when($this->filter === 'pending', fn ($q) => $q->whereNull('answered_at'))
            ->when($this->filter === 'answered', fn ($q) => $q->whereNotNull('answered_at'))
            ->with(['product', 'user'])
            ->orderByDesc('created_at')
            ->paginate(15);

        $pendingCount  = ProductQuestion::whereHas('product', fn ($q) => $q->where('expositor_id', $expositorId))->whereNull('answered_at')->count();
        $answeredCount = ProductQuestion::whereHas('product', fn ($q) => $q->where('expositor_id', $expositorId))->whereNotNull('answered_at')->count();

        return view('livewire.lojista.perguntas.pergunta-index', compact('questions', 'pendingCount', 'answeredCount'))
            ->layout('lojista.layouts.app', ['title' => 'Perguntas dos Clientes']);
    }
}
