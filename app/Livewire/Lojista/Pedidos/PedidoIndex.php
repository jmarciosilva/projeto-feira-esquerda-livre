<?php

namespace App\Livewire\Lojista\Pedidos;

use App\CustomerIntelligence\Enums\EventName;
use App\CustomerIntelligence\Facades\CustomerIntelligence;
use App\Enums\ShippingStatus;
use App\Enums\TrackingEventSource;
use App\Mail\ShipmentShippedMail;
use App\Models\OrderShipping;
use App\Models\OrderSplit;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class PedidoIndex extends Component
{
    use WithPagination;

    public string $filterStatus = '';

    // Modal: marcar como enviado
    public bool   $showShipModal  = false;
    public ?int   $shipSplitId    = null;
    public string $carrier        = '';
    public string $trackingCode   = '';
    public string $shippedAtDate  = '';

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

    public function openShipModal(int $splitId): void
    {
        $this->shipSplitId   = $splitId;
        $this->carrier       = '';
        $this->trackingCode  = '';
        $this->shippedAtDate = now()->format('Y-m-d');
        $this->showShipModal = true;
        $this->resetErrorBag();
    }

    public function closeShipModal(): void
    {
        $this->showShipModal = false;
        $this->shipSplitId   = null;
    }

    public function markAsShipped(): void
    {
        $this->validate([
            'carrier'       => 'required|string|max:100',
            'trackingCode'  => 'required|string|max:100',
            'shippedAtDate' => 'required|date|before_or_equal:today',
        ], [], [
            'carrier'       => 'transportadora',
            'trackingCode'  => 'código de rastreio',
            'shippedAtDate' => 'data de envio',
        ]);

        $split = OrderSplit::where('expositor_id', auth()->user()->expositor->id)
            ->with(['order', 'shipping'])
            ->findOrFail($this->shipSplitId);

        $shippedAt = Carbon::parse($this->shippedAtDate)->startOfDay();

        $shipping = $split->shipping ?? new OrderShipping([
            'order_id'       => $split->order_id,
            'order_split_id' => $split->id,
            'expositor_id'   => $split->expositor_id,
        ]);

        $shipping->fill([
            // O que o cliente pagou de frete para esta loja ja e fato desde o
            // checkout; o envio apenas o carrega, em vez de nascer com zero.
            'price'         => $shipping->price > 0 ? $shipping->price : ($split->shipping_amount ?? 0),
            'carrier'       => trim($this->carrier),
            'tracking_code' => strtoupper(trim($this->trackingCode)),
            'status'        => ShippingStatus::Shipped,
            'shipped_at'    => $shippedAt,
        ]);

        $shipping->save();

        $shipping->addEvent(
            ShippingStatus::Shipped->value,
            "Pedido despachado por {$split->expositor?->name} via {$this->carrier}.",
            null,
            TrackingEventSource::Manual,
        );

        $customerEmail = $split->order->customer_email ?? $split->order->user?->email;

        if (filled($customerEmail)) {
            try {
                Mail::to($customerEmail)->send(new ShipmentShippedMail($shipping->fresh(['order', 'expositor'])));
            } catch (\Throwable $exception) {
                report($exception);
            }
        }

        try {
            CustomerIntelligence::track(EventName::PedidoEnviado, [
                'pedido_id' => $split->order_id,
                'split_id' => $split->id,
                'transportadora' => $shipping->carrier,
                'codigo_rastreio' => $shipping->tracking_code,
            ], $split);
        } catch (\Throwable $exception) {
            report($exception);
        }

        $this->showShipModal = false;
        $this->shipSplitId   = null;

        session()->flash('success', "Pedido #{$split->order->reference} marcado como enviado. Cliente notificado por e-mail.");
    }

    public function render(): View
    {
        $splits = OrderSplit::where('expositor_id', auth()->user()->expositor->id)
            ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus))
            ->with([
                'order.items' => fn ($q) => $q->where('expositor_id', auth()->user()->expositor->id),
                'shipping',
            ])
            ->latest()
            ->paginate(20);

        return view('livewire.lojista.pedidos.pedido-index', compact('splits'))
            ->layout('lojista.layouts.app', ['title' => 'Meus Pedidos']);
    }
}
