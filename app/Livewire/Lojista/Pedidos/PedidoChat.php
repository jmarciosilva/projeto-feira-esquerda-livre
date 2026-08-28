<?php

namespace App\Livewire\Lojista\Pedidos;

use App\Models\OrderSplit;
use Illuminate\View\View;
use Livewire\Component;

class PedidoChat extends Component
{
    public OrderSplit $split;

    public function mount(OrderSplit $split): void
    {
        // O split historico de uma loja excluida nao tem dono: `expositor` nulo
        // nunca pode casar com o lojista autenticado.
        abort_unless(
            $split->expositor !== null && $split->expositor->user_id === auth()->id(),
            403
        );
        $this->split = $split;
    }

    public function render(): View
    {
        return view('livewire.lojista.pedidos.pedido-chat')
            ->layout('lojista.layouts.app', [
                'title' => 'Chat — Pedido #' . $this->split->order->reference,
            ]);
    }
}
