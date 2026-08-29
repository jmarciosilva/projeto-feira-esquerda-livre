<?php

namespace App\Livewire\Cliente\Pedidos;

use App\Actions\Orders\CancelOrder;
use App\Exceptions\TransicaoDePedidoInvalida;
use App\Models\Order;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class PedidoIndex extends Component
{
    use WithPagination;

    /**
     * Cancela um pedido do proprio cliente, ainda nao pago.
     *
     * A autorizacao e a mesma da listagem — `user_id` do autenticado —, e nao
     * o conhecimento da referencia: a pagina publica `/pedido/{reference}`
     * mostra qualquer pedido a quem tiver o codigo, e cancelar por ali deixaria
     * um terceiro encerrar a compra de outra pessoa.
     */
    public function cancelar(int $orderId): void
    {
        $order = Order::where('user_id', auth()->id())->findOrFail($orderId);

        try {
            app(CancelOrder::class)($order);
        } catch (TransicaoDePedidoInvalida $recusada) {
            session()->flash('error', $recusada->mensagem());

            return;
        }

        session()->flash('success', 'Pedido cancelado.');
    }

    public function render(): View
    {
        $orders = Order::where('user_id', auth()->id())
            ->latest()
            ->paginate(10);

        return view('livewire.cliente.pedidos.pedido-index', compact('orders'))
            ->layout('cliente.layouts.app', ['title' => 'Meus Pedidos']);
    }
}
