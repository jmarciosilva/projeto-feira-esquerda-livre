<?php

namespace App\Livewire\Cliente\Pedidos;

use App\Models\Order;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class PedidoIndex extends Component
{
    use WithPagination;

    public function render(): View
    {
        $orders = Order::where('user_id', auth()->id())
            ->latest()
            ->paginate(10);

        return view('livewire.cliente.pedidos.pedido-index', compact('orders'))
            ->layout('cliente.layouts.app', ['title' => 'Meus Pedidos']);
    }
}
