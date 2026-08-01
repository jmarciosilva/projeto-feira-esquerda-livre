<?php

namespace Tests\Feature\Api\V1;

use App\Models\Expositor;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CarrinhoCheckoutApiTest extends TestCase
{
    use RefreshDatabase;

    private function makeProduct(): Product
    {
        $expositor = Expositor::create(['name' => 'Ateliê das Mãos', 'slug' => 'atelie-das-maos', 'is_active' => true]);

        return Product::create([
            'expositor_id' => $expositor->id,
            'item_type' => 'produto',
            'name' => 'Bolsa Artesanal',
            'slug' => 'bolsa-artesanal',
            'price' => 89.9,
            'is_active' => true,
        ]);
    }

    public function test_cart_requires_authentication(): void
    {
        $this->getJson('/api/v1/carrinho')->assertStatus(401);
    }

    public function test_can_add_update_and_remove_cart_item(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct();
        Sanctum::actingAs($user);

        $add = $this->postJson('/api/v1/carrinho/itens', ['product_id' => $product->id, 'quantity' => 2]);
        $add->assertCreated()->assertJsonPath('total', 179.8)->assertJsonPath('count', 2);

        $itemId = $this->getJson('/api/v1/carrinho')->json('stores.0.items.0.id');

        $this->patchJson("/api/v1/carrinho/itens/{$itemId}", ['quantity' => 1])
            ->assertOk()
            ->assertJsonPath('count', 1);

        $this->deleteJson("/api/v1/carrinho/itens/{$itemId}")
            ->assertOk()
            ->assertJsonPath('count', 0);
    }

    public function test_cannot_manipulate_another_users_cart_item(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $product = $this->makeProduct();

        Sanctum::actingAs($owner);
        $this->postJson('/api/v1/carrinho/itens', ['product_id' => $product->id]);
        $itemId = $this->getJson('/api/v1/carrinho')->json('stores.0.items.0.id');

        Sanctum::actingAs($intruder);
        $this->patchJson("/api/v1/carrinho/itens/{$itemId}", ['quantity' => 5])->assertStatus(404);
    }

    public function test_checkout_creates_order_from_cart_for_pickup(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/carrinho/itens', ['product_id' => $product->id, 'quantity' => 1]);

        $response = $this->postJson('/api/v1/checkout', [
            'customer_name' => 'Maria Compradora',
            'customer_whatsapp' => '11999998888',
            'delivery_type' => 'retirada',
        ]);

        $response->assertCreated()
            ->assertJsonPath('order.status', 'aguardando_pagamento')
            ->assertJsonPath('order.total_amount', 89.9);

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_splits', 1);
        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_checkout_fails_with_empty_cart(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/v1/checkout', [
            'customer_name' => 'Maria Compradora',
            'customer_whatsapp' => '11999998888',
            'delivery_type' => 'retirada',
        ])->assertStatus(422);
    }

    public function test_checkout_requires_address_for_physical_delivery(): void
    {
        $user = User::factory()->create();
        $product = $this->makeProduct();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/carrinho/itens', ['product_id' => $product->id]);

        $this->postJson('/api/v1/checkout', [
            'customer_name' => 'Maria Compradora',
            'customer_whatsapp' => '11999998888',
            'delivery_type' => 'entrega',
        ])->assertStatus(422)->assertJsonValidationErrors(['customer_address_id']);
    }
}
