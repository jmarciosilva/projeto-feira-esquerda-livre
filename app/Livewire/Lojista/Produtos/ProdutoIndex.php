<?php

namespace App\Livewire\Lojista\Produtos;

use App\Actions\Catalog\DeleteProductOffer;
use App\Exceptions\OfertaComReservaAtiva;
use App\Models\ProductOffer;
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

        // Uma escrita só, e é a da oferta.
        //
        // Até a CAT-DOM-02B isto atualizava `products.is_active` junto, em
        // espelho. A D-CAT-10 separou os dois estados: `products.is_active` é a
        // validade canônica do item no catálogo e pertence à curadoria;
        // `product_offers.is_active` é a disponibilidade comercial desta loja e
        // continua sendo do lojista.
        //
        // Ele não perde nada com a separação — `ProductOffer::scopeVigente()`
        // exige oferta ativa, então desligar aqui já tira o item de todas as
        // vitrines. O que ele deixa de conseguir é retirar do catálogo um item
        // de que outras lojas podem depender.
        $offer->update(['is_active' => ! $offer->is_active]);
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

        try {
            app(DeleteProductOffer::class)($offer);
        } catch (OfertaComReservaAtiva $reservada) {
            // Ha pedidos pendentes segurando unidades desta oferta. O lojista
            // precisa saber disso e da saida que existe — desativar.
            session()->flash('error', $reservada->mensagemParaOLojista());

            return;
        }

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
