<?php

namespace App\Livewire\Lojista\Pedidos;

use App\Models\OrderSplit;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class PedidoIndex extends Component
{
    use WithPagination;

    public string $filterStatus = '';

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }

    public function confirmar(int $splitId): void
    {
        $split = OrderSplit::where('expositor_id', auth()->user()->expositor->id)
            ->findOrFail($splitId);

        $split->confirmar();

        session()->flash('success', 'Pagamento confirmado para o pedido #' . $split->order->reference . '.');
    }

    public function render(): View
    {
        $splits = OrderSplit::where('expositor_id', auth()->user()->expositor->id)
            ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus))
            ->with(['order.items' => fn ($q) => $q->where('expositor_id', auth()->user()->expositor->id)])
            ->latest()
            ->paginate(20);

        return view('livewire.lojista.pedidos.pedido-index', compact('splits'))
            ->layout('lojista.layouts.app', ['title' => 'Meus Pedidos']);
    }
}
