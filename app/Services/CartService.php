<?php

namespace App\Services;

use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class CartService
{
    private function sessionId(): string
    {
        return Session::getId();
    }

    private function baseQuery()
    {
        return CartItem::where('session_id', $this->sessionId());
    }

    public function add(Product $product, int $qty = 1): void
    {
        $existing = $this->baseQuery()->where('product_id', $product->id)->first();

        if ($existing) {
            $existing->increment('quantity', $qty);
            return;
        }

        CartItem::create([
            'session_id'     => $this->sessionId(),
            'user_id'        => Auth::id(),
            'product_id'     => $product->id,
            'expositor_id'   => $product->expositor_id,
            'quantity'        => $qty,
            'price_snapshot' => $product->price ?? 0,
        ]);
    }

    public function update(int $cartItemId, int $qty): void
    {
        $item = $this->baseQuery()->findOrFail($cartItemId);
        if ($qty < 1) {
            $item->delete();
            return;
        }
        $item->update(['quantity' => $qty]);
    }

    public function remove(int $cartItemId): void
    {
        $this->baseQuery()->where('id', $cartItemId)->delete();
    }

    public function clear(): void
    {
        $this->baseQuery()->delete();
    }

    /** Retorna itens agrupados por expositor_id. */
    public function items(): Collection
    {
        return $this->baseQuery()
            ->with(['product', 'expositor'])
            ->get();
    }

    public function count(): int
    {
        return (int) $this->baseQuery()->sum('quantity');
    }

    public function total(): float
    {
        return (float) $this->baseQuery()
            ->selectRaw('SUM(price_snapshot * quantity) as total')
            ->value('total');
    }

    /** Agrupa itens por expositor para exibição no carrinho. */
    public function grouped(): Collection
    {
        return $this->items()->groupBy('expositor_id');
    }
}
