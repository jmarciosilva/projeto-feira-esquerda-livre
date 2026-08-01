<?php

namespace App\Http\Controllers\Api\V1\Lojista;

use App\Enums\ShippingStatus;
use App\Enums\TrackingEventSource;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Lojista\MarkShippedRequest;
use App\Http\Resources\Api\V1\OrderSplitResource;
use App\Mail\ShipmentShippedMail;
use App\Models\OrderShipping;
use App\Models\OrderSplit;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Throwable;

class PedidoController extends Controller
{
    /** GET /api/v1/lojista/pedidos */
    public function index(Request $request): AnonymousResourceCollection
    {
        $expositorId = $request->user()->expositor->id;
        $status = $request->input('status');

        $splits = OrderSplit::where('expositor_id', $expositorId)
            ->when($status, fn ($q) => $q->where('status', $status))
            ->with([
                'order.items' => fn ($q) => $q->where('expositor_id', $expositorId),
                'expositor',
                'shipping',
            ])
            ->latest()
            ->paginate(20);

        return OrderSplitResource::collection($splits);
    }

    /** PATCH /api/v1/lojista/pedidos/{split}/confirmar-pagamento */
    public function confirmarPagamento(Request $request, int $split): JsonResponse
    {
        $orderSplit = OrderSplit::where('expositor_id', $request->user()->expositor->id)->findOrFail($split);
        $orderSplit->confirmar();

        return response()->json(['message' => "Pagamento confirmado para o pedido #{$orderSplit->order->reference}."]);
    }

    /** PATCH /api/v1/lojista/pedidos/{split}/marcar-enviado */
    public function marcarEnviado(MarkShippedRequest $request, int $split): JsonResponse
    {
        $data = $request->validated();

        $orderSplit = OrderSplit::where('expositor_id', $request->user()->expositor->id)
            ->with(['order', 'shipping'])
            ->findOrFail($split);

        $shipping = DB::transaction(function () use ($orderSplit, $data) {
            $shipping = $orderSplit->shipping ?? new OrderShipping([
                'order_id' => $orderSplit->order_id,
                'order_split_id' => $orderSplit->id,
                'expositor_id' => $orderSplit->expositor_id,
            ]);

            $shipping->fill([
                'carrier' => trim($data['carrier']),
                'tracking_code' => strtoupper(trim($data['tracking_code'])),
                'status' => ShippingStatus::Shipped,
                'shipped_at' => Carbon::parse($data['shipped_at'])->startOfDay(),
            ]);
            $shipping->save();

            $shipping->addEvent(
                ShippingStatus::Shipped->value,
                "Pedido despachado por {$orderSplit->expositor?->name} via {$data['carrier']}.",
                null,
                TrackingEventSource::Manual,
            );

            return $shipping;
        });

        // Envio de e-mail fica fora da transação: é I/O externo e não deve segurar o lock do banco.
        $customerEmail = $orderSplit->order->customer_email ?? $orderSplit->order->user?->email;

        if (filled($customerEmail)) {
            try {
                Mail::to($customerEmail)->send(new ShipmentShippedMail($shipping->fresh(['order', 'expositor'])));
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        return response()->json(['message' => "Pedido #{$orderSplit->order->reference} marcado como enviado. Cliente notificado por e-mail."]);
    }
}
