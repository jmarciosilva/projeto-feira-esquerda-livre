<?php

namespace App\Livewire\Lojista\Produtos;

use App\Models\Product;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class ProdutoIndex extends Component
{
    use WithPagination;

    public string $search     = '';
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
        $product = $this->expositorProdutos()->findOrFail($id);
        $product->update(['is_active' => ! $product->is_active]);
    }

    public function delete(int $id): void
    {
        $product = $this->expositorProdutos()->findOrFail($id);
        $product->delete();
        session()->flash('success', 'Produto removido.');
    }

    private function expositorProdutos()
    {
        return Product::where('expositor_id', auth()->user()->expositor->id);
    }

    public function render(): View
    {
        $products = $this->expositorProdutos()
            ->when($this->search,     fn ($q) => $q->where('name', 'like', "%{$this->search}%"))
            ->when($this->filterType, fn ($q) => $q->where('item_type', $this->filterType))
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('livewire.lojista.produtos.produto-index', compact('products'))
            ->layout('lojista.layouts.app', ['title' => 'Meus Cadastros']);
    }
}
