<?php

namespace App\Livewire;

use App\Models\ProductOffer;
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

    /**
     * O carrinho recebe uma oferta, não um produto.
     *
     * Um item de catálogo não tem preço nem dono — quem tem é a oferta de um
     * expositor sobre ele. Adicionar "o produto" deixaria em aberto de qual
     * loja e por quanto, e é exatamente essa ambiguidade que a CAT-DOM-01
     * eliminou.
     */
    #[On('add-to-cart')]
    public function addToCart(int $offerId, CartService $cart): void
    {
        $offer = ProductOffer::with(['product', 'expositor'])->find($offerId);

        if ($offer && $offer->isVigente()) {
            $cart->add($offer);
            $this->open = true;
            $this->dispatch('cart-updated');
        }
    }

    public function render(CartService $cart)
    {
        return view('livewire.cart-drawer', [
            'grouped' => $cart->grouped(),
            'total' => $cart->total(),
            'count' => $cart->count(),
        ]);
    }
}
