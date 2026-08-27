<?php

namespace App\Livewire\Lojista\Produtos;

use App\Models\ProductOffer;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class ProdutoIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public string $filterType = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterType(): void
    {
        $this->resetPage();
    }

    public function toggleActive(int $id): void
    {
        $offer = $this->ofertasDoExpositor()->findOrFail($id);

        // As duas escritas mudam juntas ou nenhuma muda: a oferta é a fonte de
        // verdade, e `products.is_active` é o espelho da dívida D-1. Deixar a
        // segunda falhar sozinha produziria a divergência que o espelho existe
        // justamente para evitar.
        DB::transaction(function () use ($offer) {
            $offer->update(['is_active' => ! $offer->is_active]);
            $offer->product->update(['is_active' => $offer->is_active]);
        });
    }

    /**
     * Remove a oferta, não o item do catálogo.
     *
     * É a diferença que a CAT-DOM-01 introduziu: o lojista tira o item da
     * *sua* loja. O produto continua no catálogo, com as descrições e o
     * conhecimento que a Catalog Intelligence acumulou, disponível para quando
     * ele — ou outro expositor — voltar a oferecê-lo. Apagar o produto
     * destruiria memória que não pertence a uma loja só.
     */
    public function delete(int $id): void
    {
        $offer = $this->ofertasDoExpositor()->findOrFail($id);
        $offer->delete();

        session()->flash('success', 'Item removido da sua loja.');
    }

    private function ofertasDoExpositor()
    {
        return ProductOffer::where('expositor_id', auth()->user()->expositor->id);
    }

    public function render(): View
    {
        $offers = $this->ofertasDoExpositor()
            ->with(['product.category'])
            ->when(
                $this->search,
                fn ($q) => $q->whereHas('product', fn ($p) => $p->where('name', 'like', "%{$this->search}%"))
            )
            ->when(
                $this->filterType,
                fn ($q) => $q->whereHas('product', fn ($p) => $p->where('item_type', $this->filterType))
            )
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('livewire.lojista.produtos.produto-index', compact('offers'))
            ->layout('lojista.layouts.app', ['title' => 'Meus Cadastros']);
    }
}
