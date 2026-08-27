<?php

namespace Tests\Feature;

use App\Models\CartItem;
use App\Models\Expositor;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cart_is_merged_into_user_cart_after_login(): void
    {
        $user = User::factory()->create();
        $expositor = Expositor::create([
            'name' => 'Atelie das Maos',
            'slug' => 'atelie-das-maos',
        ]);

        $bag = Product::factory()->create([
            'expositor_id' => $expositor->id,
            'name' => 'Bolsa Tecida Artesanal',
            'slug' => 'bolsa-tecida-artesanal',
            'price' => 89.90,
            'is_active' => true,
        ]);

        $book = Product::factory()->create([
            'expositor_id' => $expositor->id,
            'name' => 'Livro Popular',
            'slug' => 'livro-popular',
            'price' => 40.00,
            'is_active' => true,
        ]);

        CartItem::create([
            'session_id' => 'old-user-session',
            'user_id' => $user->id,
            'product_id' => $bag->id,
            'expositor_id' => $expositor->id,
            'quantity' => 1,
            'price_snapshot' => 89.90,
        ]);

        CartItem::create([
            'session_id' => 'guest-session',
            'product_id' => $bag->id,
            'expositor_id' => $expositor->id,
            'quantity' => 2,
            'price_snapshot' => 89.90,
        ]);

        CartItem::create([
            'session_id' => 'guest-session',
            'product_id' => $book->id,
            'expositor_id' => $expositor->id,
            'quantity' => 1,
            'price_snapshot' => 40.00,
        ]);

        app(CartService::class)->reassignSession('guest-session', $user->id);

        $this->assertDatabaseCount('cart_items', 2);
        $this->assertDatabaseHas('cart_items', [
            'user_id' => $user->id,
            'product_id' => $bag->id,
            'quantity' => 3,
        ]);
        $this->assertDatabaseHas('cart_items', [
            'user_id' => $user->id,
            'product_id' => $book->id,
            'quantity' => 1,
        ]);
        $this->assertDatabaseMissing('cart_items', [
            'session_id' => 'guest-session',
            'user_id' => null,
        ]);
    }
}
