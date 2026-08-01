<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CheckoutRequest;
use App\Http\Resources\Api\V1\OrderResource;
use App\Services\CartService;
use App\Services\MercadoPagoService;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Throwable;

class CheckoutController extends Controller
{
    /** POST /api/v1/checkout */
    public function store(
        CheckoutRequest $request,
        OrderService $orderService,
        CartService $cart,
        MercadoPagoService $mercadoPago
    ): JsonResponse {
        $user = $request->user();

        if (! $user->isMarketplaceActive()) {
            throw ValidationException::withMessages([
                'customer_name' => ['Sua conta de cliente está inativa no marketplace.'],
            ]);
        }

        $items = $cart->items();

        if ($items->isEmpty()) {
            throw ValidationException::withMessages([
                'customer_name' => ['Seu carrinho está vazio.'],
            ]);
        }

        $data = $request->validated();
        $allDigital = $items->every(fn ($item) => $item->product?->is_digital);
        $address = null;

        if ($data['delivery_type'] === 'entrega' && ! $allDigital) {
            $address = $user->addresses()->find($data['customer_address_id'] ?? null);

            if (! $address) {
                throw ValidationException::withMessages([
                    'customer_address_id' => ['Selecione ou cadastre um endereço de entrega.'],
                ]);
            }
        }

        $order = $orderService->createFromCart([
            'customer_name' => $data['customer_name'],
            'customer_whatsapp' => $data['customer_whatsapp'],
            'customer_email' => $data['customer_email'] ?? null,
            'delivery_type' => $data['delivery_type'],
            'customer_address_id' => $address?->id,
            'address_cep' => $address?->cep,
            'address_rua' => $address?->rua,
            'address_numero' => $address?->numero,
            'address_complemento' => $address?->complemento,
            'address_bairro' => $address?->bairro,
            'address_cidade' => $address?->cidade,
            'address_estado' => $address?->estado,
            'shipping_total' => $data['shipping_total'] ?? 0,
            'shipping_note' => $data['shipping_note'] ?? null,
        ], $cart);

        if ($mercadoPago->isEnabled()) {
            try {
                $order = $mercadoPago->createPreference($order);
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        return response()->json([
            'order' => new OrderResource($order->load(['items', 'splits.expositor'])),
        ], 201);
    }
}
