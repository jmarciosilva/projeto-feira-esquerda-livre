<?php

namespace Tests\Feature\Api\V1;

use App\Enums\UserRole;
use App\Models\Ava\AvaCourse;
use App\Models\Expositor;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderSplit;
use App\Models\Product;
use App\Models\ProductQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LojistaApiTest extends TestCase
{
    use RefreshDatabase;

    private static int $lojistaCounter = 0;

    private function makeLojista(bool $expositorActive = true): array
    {
        self::$lojistaCounter++;

        $user = User::factory()->create(['role' => UserRole::Lojista]);
        $expositor = Expositor::create([
            'name' => 'Ateliê das Mãos '.self::$lojistaCounter,
            'slug' => 'atelie-das-maos-'.self::$lojistaCounter,
            'user_id' => $user->id,
            'is_active' => $expositorActive,
        ]);

        return compact('user', 'expositor');
    }

    public function test_non_lojista_is_forbidden(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/v1/lojista/painel')->assertStatus(403);
    }

    public function test_painel_returns_summary(): void
    {
        ['user' => $user] = $this->makeLojista();
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/lojista/painel')
            ->assertOk()
            ->assertJsonPath('total_produtos', 0);
    }

    public function test_can_view_and_update_loja(): void
    {
        ['user' => $user, 'expositor' => $expositor] = $this->makeLojista();
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/lojista/loja')->assertOk()->assertJsonPath('data.name', $expositor->name);

        $this->putJson('/api/v1/lojista/loja', [
            'description' => 'Artesanato popular e solidário.',
            'city' => 'São Paulo',
            'state' => 'sp',
        ])->assertOk()
            ->assertJsonPath('data.description', 'Artesanato popular e solidário.')
            ->assertJsonPath('data.state', 'SP');
    }

    public function test_produto_crud(): void
    {
        ['user' => $user, 'expositor' => $expositor] = $this->makeLojista();
        Sanctum::actingAs($user);

        $store = $this->postJson('/api/v1/lojista/produtos', [
            'item_type' => 'produto',
            'name' => 'Bolsa Artesanal',
            'price' => 89.9,
            'has_stock' => true,
            'stock_quantity' => 10,
        ]);
        $store->assertCreated()->assertJsonPath('data.name', 'Bolsa Artesanal');
        $productId = $store->json('data.id');

        $this->getJson('/api/v1/lojista/produtos')->assertOk()->assertJsonCount(1, 'data');

        $this->putJson("/api/v1/lojista/produtos/{$productId}", [
            'item_type' => 'produto',
            'name' => 'Bolsa Artesanal Grande',
            'price' => 99.9,
            'has_stock' => true,
            'stock_quantity' => 5,
        ])->assertOk()->assertJsonPath('data.name', 'Bolsa Artesanal Grande');

        $this->deleteJson("/api/v1/lojista/produtos/{$productId}")->assertNoContent();
        $this->assertDatabaseCount('products', 0);

        // Confere que o produto pertence à loja do lojista autenticado
        $this->assertDatabaseMissing('products', ['expositor_id' => $expositor->id]);
    }

    public function test_cannot_edit_another_lojistas_product(): void
    {
        ['user' => $ownerUser, 'expositor' => $ownerExpositor] = $this->makeLojista();
        ['user' => $otherUser] = $this->makeLojista();

        $product = Product::create([
            'expositor_id' => $ownerExpositor->id,
            'item_type' => 'produto',
            'name' => 'Bolsa Artesanal',
            'slug' => 'bolsa-artesanal',
            'price' => 89.9,
            'is_active' => true,
        ]);

        Sanctum::actingAs($otherUser);
        $this->putJson("/api/v1/lojista/produtos/{$product->id}", [
            'item_type' => 'produto',
            'name' => 'Hackeado',
        ])->assertStatus(403);
    }

    public function test_can_confirm_payment_and_mark_as_shipped(): void
    {
        Mail::fake();
        ['user' => $user, 'expositor' => $expositor] = $this->makeLojista();

        $order = Order::create([
            'customer_name' => 'Maria Compradora',
            'customer_whatsapp' => '11999998888',
            'delivery_type' => 'entrega',
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

        $split = OrderSplit::create([
            'order_id' => $order->id,
            'expositor_id' => $expositor->id,
            'gross_amount' => 89.9,
            'net_amount' => 89.9,
            'status' => 'pendente',
        ]);

        Sanctum::actingAs($user);

        $this->patchJson("/api/v1/lojista/pedidos/{$split->id}/confirmar-pagamento")->assertOk();
        $this->assertDatabaseHas('order_splits', ['id' => $split->id, 'status' => 'confirmado']);

        $this->patchJson("/api/v1/lojista/pedidos/{$split->id}/marcar-enviado", [
            'carrier' => 'Correios',
            'tracking_code' => 'AA123456789BR',
            'shipped_at' => now()->format('Y-m-d'),
        ])->assertOk();

        $this->assertDatabaseHas('order_shippings', [
            'order_split_id' => $split->id,
            'carrier' => 'Correios',
            'tracking_code' => 'AA123456789BR',
        ]);
    }

    public function test_can_answer_and_toggle_question_visibility(): void
    {
        ['user' => $user, 'expositor' => $expositor] = $this->makeLojista();
        $product = Product::create([
            'expositor_id' => $expositor->id,
            'item_type' => 'produto',
            'name' => 'Bolsa Artesanal',
            'slug' => 'bolsa-artesanal',
            'price' => 89.9,
            'is_active' => true,
        ]);
        $question = ProductQuestion::create([
            'product_id' => $product->id,
            'user_id' => User::factory()->create()->id,
            'question' => 'Tem em outra cor?',
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/lojista/perguntas')
            ->assertOk()
            ->assertJsonPath('meta.pending_count', 1);

        $this->patchJson("/api/v1/lojista/perguntas/{$question->id}/responder", ['answer' => 'Sim, temos em azul.'])
            ->assertOk()
            ->assertJsonPath('data.answer', 'Sim, temos em azul.');

        $this->patchJson("/api/v1/lojista/perguntas/{$question->id}/visibilidade")
            ->assertOk()
            ->assertJsonPath('data.is_visible', false);
    }

    public function test_exposicao_returns_off_home_when_not_featured(): void
    {
        ['user' => $user] = $this->makeLojista();
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/lojista/exposicao')->assertOk()->assertJsonPath('on_home', false);
    }

    public function test_cursos_list_and_publish_toggle(): void
    {
        ['user' => $user, 'expositor' => $expositor] = $this->makeLojista();
        $product = Product::create([
            'expositor_id' => $expositor->id,
            'item_type' => 'servico',
            'name' => 'Curso de Culinária',
            'slug' => 'curso-de-culinaria',
            'price' => 49.9,
            'is_digital' => true,
            'is_active' => true,
        ]);
        $course = AvaCourse::create(['product_id' => $product->id]);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/lojista/cursos')
            ->assertOk()
            ->assertJsonPath('cursos.0.is_published', false);

        $this->patchJson("/api/v1/lojista/cursos/{$course->id}/publicar")
            ->assertOk()
            ->assertJsonPath('is_published', true);
    }

    public function test_inactive_expositor_is_forbidden(): void
    {
        ['user' => $user] = $this->makeLojista(expositorActive: false);
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/lojista/painel')->assertStatus(403);
    }
}
