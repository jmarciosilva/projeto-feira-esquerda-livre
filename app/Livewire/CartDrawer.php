<?php

namespace App\Livewire;

use App\Services\CartService;
use Livewire\Attributes\On;
use Livewire\Component;

class CartDrawer extends Component
{
    public bool $open = false;

    #[On('cart-updated')]
    public function refresh(): void {}

    public function toggle(): void
    {
        $this->open = ! $this->open;
    }

    public function updateQty(int $itemId, int $qty, CartService $cart): void
    {
        $cart->update($itemId, $qty);
        $this->dispatch('cart-updated');
    }

    public function remove(int $itemId, CartService $cart): void
    {
        $cart->remove($itemId);
        $this->dispatch('cart-updated');
    }

    public function addToCart(int $productId, CartService $cart): void
    {
        $product = \App\Models\Product::find($productId);
        if ($product && $product->is_active) {
            $cart->add($product);
            $this->open = true;
            $this->dispatch('cart-updated');
        }
    }

    public function render(CartService $cart)
    {
        return view('livewire.cart-drawer', [
            'grouped' => $cart->grouped(),
            'total'   => $cart->total(),
            'count'   => $cart->count(),
        ]);
    }
}
