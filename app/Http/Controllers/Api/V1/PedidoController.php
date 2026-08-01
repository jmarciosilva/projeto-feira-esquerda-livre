<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\OrderResource;
use App\Models\Order;
use App\Services\MercadoPagoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Throwable;

class PedidoController extends Controller
{
    /** GET /api/v1/pedidos */
    public function index(Request $request): AnonymousResourceCollection
    {
        $orders = $request->user()->orders()
            ->with(['items', 'splits.expositor'])
            ->orderByDesc('created_at')
            ->paginate(15);

        return OrderResource::collection($orders);
    }

    /** GET /api/v1/pedidos/{reference} */
    public function show(Request $request, string $reference): OrderResource
    {
        $order = $request->user()->orders()
            ->where('reference', $reference)
            ->with(['items', 'splits.expositor', 'splits.shipping'])
            ->firstOrFail();

        return new OrderResource($order);
    }

    /** GET /api/v1/pedidos/{reference}/pagar */
    public function pagar(Request $request, string $reference, MercadoPagoService $mercadoPago): JsonResponse
    {
        $order = $request->user()->orders()->where('reference', $reference)->firstOrFail();

        if ($order->status === OrderStatus::PagamentoConfirmado) {
            return response()->json(['message' => 'Este pedido já está com pagamento confirmado.']);
        }

        try {
            if (! $order->mercado_pago_preference_id) {
                $order = $mercadoPago->createPreference($order);
            }

            return response()->json(['checkout_url' => $mercadoPago->checkoutUrl($order)]);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json(['message' => 'Não foi possível abrir o Mercado Pago agora. Tente novamente em alguns instantes.'], 422);
        }
    }
}
