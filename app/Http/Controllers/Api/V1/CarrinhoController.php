<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreCartItemRequest;
use App\Http\Requests\Api\V1\UpdateCartItemRequest;
use App\Http\Resources\Api\V1\CartItemResource;
use App\Models\Product;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;

class CarrinhoController extends Controller
{
    /** GET /api/v1/carrinho */
    public function index(CartService $cart): JsonResponse
    {
        return $this->summary($cart);
    }

    /** POST /api/v1/carrinho/itens */
    public function store(StoreCartItemRequest $request, CartService $cart): JsonResponse
    {
        $product = Product::where('is_active', true)->findOrFail($request->validated('product_id'));

        $cart->add($product, (int) ($request->validated('quantity') ?? 1));

        return $this->summary($cart, 201);
    }

    /** PATCH /api/v1/carrinho/itens/{item} */
    public function update(UpdateCartItemRequest $request, int $item, CartService $cart): JsonResponse
    {
        $cart->update($item, (int) $request->validated('quantity'));

        return $this->summary($cart);
    }

    /** DELETE /api/v1/carrinho/itens/{item} */
    public function destroy(int $item, CartService $cart): JsonResponse
    {
        $cart->remove($item);

        return $this->summary($cart);
    }

    private function summary(CartService $cart, int $status = 200): JsonResponse
    {
        $grouped = $cart->grouped()->map(function ($items) {
            $items = $items->values();

            return [
                'expositor_id' => $items->first()?->expositor_id,
                'expositor_name' => $items->first()?->expositor?->name,
                'subtotal' => $items->sum(fn ($item) => $item->subtotal()),
                'items' => CartItemResource::collection($items),
            ];
        })->values();

        return response()->json([
            'stores' => $grouped,
            'total' => $cart->total(),
            'count' => $cart->count(),
        ], $status);
    }
}
