<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreOrderMessageRequest;
use App\Http\Resources\Api\V1\OrderMessageResource;
use App\Models\OrderMessage;
use App\Models\OrderSplit;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PedidoMensagemController extends Controller
{
    /**
     * Mesma regra de autorização do componente Livewire OrderChat
     * (app/Livewire/OrderChat.php): dono do pedido OU dono da loja do split.
     */
    private function authorizeAccess(Request $request, OrderSplit $split): void
    {
        $userId = $request->user()->id;
        $isCustomer = $split->order->user_id === $userId;
        $isLojista = $split->expositor->user_id === $userId;

        abort_unless($isCustomer || $isLojista, 403);
    }

    /** GET /api/v1/pedidos/splits/{split}/mensagens */
    public function index(Request $request, OrderSplit $split): AnonymousResourceCollection
    {
        $this->authorizeAccess($request, $split);

        OrderMessage::where('order_split_id', $split->id)
            ->where('sender_id', '!=', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        $messages = OrderMessage::where('order_split_id', $split->id)
            ->with('sender')
            ->orderBy('created_at')
            ->get();

        return OrderMessageResource::collection($messages);
    }

    /** POST /api/v1/pedidos/splits/{split}/mensagens */
    public function store(StoreOrderMessageRequest $request, OrderSplit $split): OrderMessageResource
    {
        $this->authorizeAccess($request, $split);

        $message = OrderMessage::create([
            'order_split_id' => $split->id,
            'sender_id' => $request->user()->id,
            'body' => trim($request->validated('body')),
        ]);

        return new OrderMessageResource($message->load('sender'));
    }
}
