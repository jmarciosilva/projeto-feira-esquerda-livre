<?php

namespace App\Livewire;

use App\Models\OrderMessage;
use App\Models\OrderSplit;
use Illuminate\View\View;
use Livewire\Component;

class OrderChat extends Component
{
    public OrderSplit $split;
    public string $body = '';

    public function mount(): void
    {
        $this->authorizeAccess();
    }

    public function send(): void
    {
        $this->authorizeAccess();
        $this->validate(['body' => 'required|string|max:2000']);

        OrderMessage::create([
            'order_split_id' => $this->split->id,
            'sender_id'      => auth()->id(),
            'body'           => trim($this->body),
        ]);

        $this->body = '';
        $this->dispatch('chat-message-sent');
    }

    public function render(): View
    {
        $this->markRead();

        $messages = OrderMessage::where('order_split_id', $this->split->id)
            ->with('sender')
            ->orderBy('created_at')
            ->get();

        return view('livewire.order-chat', compact('messages'));
    }

    private function authorizeAccess(): void
    {
        $userId     = auth()->id();
        $isCustomer = $this->split->order->user_id === $userId;
        $isLojista  = $this->split->expositor->user_id === $userId;
        abort_unless($isCustomer || $isLojista, 403);
    }

    private function markRead(): void
    {
        OrderMessage::where('order_split_id', $this->split->id)
            ->where('sender_id', '!=', auth()->id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }
}
