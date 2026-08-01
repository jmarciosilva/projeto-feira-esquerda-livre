<?php

namespace Tests\Feature\Api\V1;

use App\Enums\UserRole;
use App\Models\Expositor;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderSplit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PedidoChatEnderecoApiTest extends TestCase
{
    use RefreshDatabase;

    private function makeOrderWithSplit(User $customer, ?User $lojistaUser = null): OrderSplit
    {
        $expositor = Expositor::create([
            'name' => 'Ateliê das Mãos',
            'slug' => 'atelie-das-maos',
            'is_active' => true,
            'user_id' => $lojistaUser?->id,
        ]);

        $order = Order::create([
            'user_id' => $customer->id,
            'customer_name' => $customer->name,
            'customer_whatsapp' => '11999998888',
            'delivery_type' => 'retirada',
            'items_total' => 89.9,
            'shipping_total' => 0,
            'total_amount' => 89.9,
            'status' => 'aguardando_pagamento',
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'expositor_id' => $expositor->id,
            'product_name' => 'Bolsa Artesanal',
            'unit_price' => 89.9,
            'quantity' => 1,
            'total_price' => 89.9,
        ]);

        return OrderSplit::create([
            'order_id' => $order->id,
            'expositor_id' => $expositor->id,
            'gross_amount' => 89.9,
            'net_amount' => 89.9,
            'status' => 'pendente',
        ]);
    }

    public function test_customer_can_list_and_view_own_orders(): void
    {
        $customer = User::factory()->create();
        $this->makeOrderWithSplit($customer);
        Sanctum::actingAs($customer);

        $this->getJson('/api/v1/pedidos')->assertOk()->assertJsonCount(1, 'data');

        $reference = Order::first()->reference;
        $this->getJson("/api/v1/pedidos/{$reference}")->assertOk()->assertJsonPath('data.reference', $reference);
    }

    public function test_customer_cannot_view_other_customers_order(): void
    {
        $customer = User::factory()->create();
        $stranger = User::factory()->create();
        $this->makeOrderWithSplit($customer);

        Sanctum::actingAs($stranger);
        $reference = Order::first()->reference;
        $this->getJson("/api/v1/pedidos/{$reference}")->assertNotFound();
    }

    public function test_customer_and_lojista_can_chat_but_third_party_cannot(): void
    {
        $customer = User::factory()->create();
        $lojistaUser = User::factory()->create(['role' => UserRole::Lojista]);
        $stranger = User::factory()->create();
        $split = $this->makeOrderWithSplit($customer, $lojistaUser);

        Sanctum::actingAs($customer);
        $this->postJson("/api/v1/pedidos/splits/{$split->id}/mensagens", ['body' => 'Olá, chegou meu pedido?'])
            ->assertCreated()
            ->assertJsonPath('data.body', 'Olá, chegou meu pedido?');

        Sanctum::actingAs($lojistaUser);
        $this->getJson("/api/v1/pedidos/splits/{$split->id}/mensagens")
            ->assertOk()
            ->assertJsonCount(1, 'data');

        Sanctum::actingAs($stranger);
        $this->getJson("/api/v1/pedidos/splits/{$split->id}/mensagens")->assertStatus(403);
        $this->postJson("/api/v1/pedidos/splits/{$split->id}/mensagens", ['body' => 'oi'])->assertStatus(403);
    }

    public function test_endereco_crud(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $store = $this->postJson('/api/v1/enderecos', [
            'label' => 'Casa',
            'cep' => '01001-000',
            'rua' => 'Praça da Sé',
            'numero' => '100',
            'bairro' => 'Sé',
            'cidade' => 'São Paulo',
            'estado' => 'sp',
        ]);
        $store->assertCreated()->assertJsonPath('data.estado', 'SP')->assertJsonPath('data.is_default', true);
        $addressId = $store->json('data.id');

        $this->getJson('/api/v1/enderecos')->assertOk()->assertJsonCount(1, 'data');

        $this->putJson("/api/v1/enderecos/{$addressId}", [
            'label' => 'Trabalho',
            'cep' => '01001-000',
            'rua' => 'Praça da Sé',
            'numero' => '200',
            'bairro' => 'Sé',
            'cidade' => 'São Paulo',
            'estado' => 'SP',
        ])->assertOk()->assertJsonPath('data.label', 'Trabalho');

        $this->deleteJson("/api/v1/enderecos/{$addressId}")->assertNoContent();
        $this->assertDatabaseCount('customer_addresses', 0);
    }

    public function test_cannot_update_another_users_address(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();

        Sanctum::actingAs($owner);
        $addressId = $this->postJson('/api/v1/enderecos', [
            'label' => 'Casa',
            'cep' => '01001-000',
            'rua' => 'Praça da Sé',
            'numero' => '100',
            'bairro' => 'Sé',
            'cidade' => 'São Paulo',
            'estado' => 'SP',
        ])->json('data.id');

        Sanctum::actingAs($intruder);
        $this->putJson("/api/v1/enderecos/{$addressId}", [
            'label' => 'Hackeado',
            'cep' => '01001-000',
            'rua' => 'Praça da Sé',
            'numero' => '100',
            'bairro' => 'Sé',
            'cidade' => 'São Paulo',
            'estado' => 'SP',
        ])->assertStatus(403);
    }
}
