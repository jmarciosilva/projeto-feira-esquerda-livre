<?php

namespace App\Livewire\Admin\Pedidos;

use App\Enums\OrderStatus;
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

    public function updateStatus(int $orderId, string $status): void
    {
        $this->authorizeAdminAction('pedidos.atualizar_status');

        $order = Order::findOrFail($orderId);
        $order->update(['status' => $status]);

        session()->flash('success', 'Status do pedido #' . $order->reference . ' atualizado.');
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
