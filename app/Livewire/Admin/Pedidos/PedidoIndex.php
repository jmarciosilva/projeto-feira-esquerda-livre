<?php

namespace App\Livewire\Admin\Pedidos;

use App\Actions\Orders\CancelOrder;
use App\Enums\OrderStatus;
use App\Exceptions\TransicaoDePedidoInvalida;
use App\Livewire\Admin\Concerns\AuthorizesAdminActions;
use App\Models\Order;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class PedidoIndex extends Component
{
    use AuthorizesAdminActions, WithPagination;

    public string $filterStatus = '';
    public string $search       = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }

    /**
     * Cancela um pedido ainda nao pago.
     *
     * Substitui o `updateStatus` generico, que escrevia qualquer estado em
     * qualquer pedido com um `update` cru: era possivel marcar um pedido como
     * pago sem pagamento, concluir um cancelado, ou cancelar sem devolver o
     * estoque reservado. Estado nao e escolha de formulario — `Concluido`,
     * `PagamentoConfirmado`, `Expirado` e `Estornado` nascem dos eventos e
     * actions que os produzem, nunca de um select.
     */
    public function cancelar(int $orderId): void
    {
        $this->authorizeAdminAction('pedidos.atualizar_status');

        $order = Order::findOrFail($orderId);

        try {
            app(CancelOrder::class)($order);
        } catch (TransicaoDePedidoInvalida $recusada) {
            session()->flash('error', $recusada->mensagem());

            return;
        }

        session()->flash('success', 'Pedido #' . $order->reference . ' cancelado e estoque devolvido.');
    }

    public function render(): View
    {
        $orders = Order::query()
            ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus))
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('reference', 'like', "%{$this->search}%")
                  ->orWhere('customer_name', 'like', "%{$this->search}%");
            }))
            ->withCount('items')
            ->with(['splits.expositor', 'shippings'])
            ->latest()
            ->paginate(20);

        return view('livewire.admin.pedidos.pedido-index', [
            'orders'   => $orders,
            'statuses' => OrderStatus::cases(),
        ])->layout('admin.layouts.app', ['title' => 'Pedidos']);
    }
}
