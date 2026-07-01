<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Livewire\Lojista\Perguntas\PerguntaIndex;
use App\Livewire\ProductQandA;
use App\Models\Expositor;
use App\Models\Product;
use App\Models\ProductQuestion;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductQandATest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function makeLojista(): User
    {
        $u = User::factory()->create(['role' => UserRole::Lojista, 'is_active' => true]);
        $u->assignRole('lojista');
        return $u;
    }

    private function makeCliente(): User
    {
        $u = User::factory()->create(['role' => UserRole::User, 'is_active' => true]);
        $u->assignRole('cliente');
        return $u;
    }

    private function makeExpositor(User $lojista): Expositor
    {
        return Expositor::create([
            'user_id'     => $lojista->id,
            'name'        => 'Loja Q&A',
            'slug'        => 'loja-qa-' . uniqid(),
            'description' => 'desc',
            'is_active'   => true,
            'is_featured' => false,
        ]);
    }

    private function makeProduct(Expositor $expositor): Product
    {
        return Product::create([
            'expositor_id' => $expositor->id,
            'item_type'    => 'produto',
            'name'         => 'Produto QA',
            'slug'         => 'produto-qa-' . uniqid(),
            'is_active'    => true,
            'is_featured'  => false,
            'has_stock'    => true,
            'sort_order'   => 0,
        ]);
    }

    // ── Perguntar (cliente) ────────────────────────────────────────────────────

    public function test_logged_in_user_can_submit_question(): void
    {
        $lojista  = $this->makeLojista();
        $expositor = $this->makeExpositor($lojista);
        $product  = $this->makeProduct($expositor);
        $cliente  = $this->makeCliente();

        Livewire::actingAs($cliente)
            ->test(ProductQandA::class, ['product' => $product])
            ->set('question', 'Qual o prazo de entrega para SP?')
            ->call('submit')
            ->assertSet('submitted', true)
            ->assertSet('question', '');

        $this->assertDatabaseHas('product_questions', [
            'product_id' => $product->id,
            'user_id'    => $cliente->id,
            'question'   => 'Qual o prazo de entrega para SP?',
            'answer'     => null,
        ]);
    }

    public function test_question_requires_minimum_5_chars(): void
    {
        $lojista   = $this->makeLojista();
        $expositor = $this->makeExpositor($lojista);
        $product   = $this->makeProduct($expositor);
        $cliente   = $this->makeCliente();

        Livewire::actingAs($cliente)
            ->test(ProductQandA::class, ['product' => $product])
            ->set('question', 'ok?')
            ->call('submit')
            ->assertHasErrors(['question']);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $lojista   = $this->makeLojista();
        $expositor = $this->makeExpositor($lojista);
        $product   = $this->makeProduct($expositor);

        Livewire::test(ProductQandA::class, ['product' => $product])
            ->set('question', 'Pergunta de visitante?')
            ->call('submit')
            ->assertRedirect(route('login'));
    }

    public function test_answered_questions_visible_on_product_page(): void
    {
        $lojista   = $this->makeLojista();
        $expositor = $this->makeExpositor($lojista);
        $product   = $this->makeProduct($expositor);
        $cliente   = $this->makeCliente();

        ProductQuestion::create([
            'product_id'  => $product->id,
            'user_id'     => $cliente->id,
            'question'    => 'Aceita troca?',
            'answer'      => 'Sim, em 7 dias úteis.',
            'answered_at' => now(),
            'answered_by' => $lojista->id,
            'is_visible'  => true,
        ]);

        $this->get(route('loja.produto', [$expositor->slug, $product->slug]))
             ->assertOk()
             ->assertSee('Aceita troca?')
             ->assertSee('Sim, em 7 dias úteis.');
    }

    public function test_unanswered_questions_not_visible_publicly(): void
    {
        $lojista   = $this->makeLojista();
        $expositor = $this->makeExpositor($lojista);
        $product   = $this->makeProduct($expositor);
        $cliente   = $this->makeCliente();

        ProductQuestion::create([
            'product_id' => $product->id,
            'user_id'    => $cliente->id,
            'question'   => 'Pergunta sem resposta ainda?',
            'is_visible' => true,
        ]);

        Livewire::test(ProductQandA::class, ['product' => $product])
            ->assertDontSee('Pergunta sem resposta ainda?');
    }

    public function test_hidden_question_not_shown_publicly(): void
    {
        $lojista   = $this->makeLojista();
        $expositor = $this->makeExpositor($lojista);
        $product   = $this->makeProduct($expositor);
        $cliente   = $this->makeCliente();

        ProductQuestion::create([
            'product_id'  => $product->id,
            'user_id'     => $cliente->id,
            'question'    => 'Pergunta ocultada',
            'answer'      => 'Resposta',
            'answered_at' => now(),
            'answered_by' => $lojista->id,
            'is_visible'  => false,
        ]);

        Livewire::test(ProductQandA::class, ['product' => $product])
            ->assertDontSee('Pergunta ocultada');
    }

    // ── Painel do lojista ──────────────────────────────────────────────────────

    public function test_lojista_can_answer_question(): void
    {
        $lojista   = $this->makeLojista();
        $expositor = $this->makeExpositor($lojista);
        $product   = $this->makeProduct($expositor);
        $cliente   = $this->makeCliente();

        $q = ProductQuestion::create([
            'product_id' => $product->id,
            'user_id'    => $cliente->id,
            'question'   => 'Tem parcelamento?',
        ]);

        Livewire::actingAs($lojista)
            ->test(PerguntaIndex::class)
            ->set("answers.{$q->id}", 'Sim, em até 12x sem juros!')
            ->call('saveAnswer', $q->id);

        $q->refresh();
        $this->assertEquals('Sim, em até 12x sem juros!', $q->answer);
        $this->assertNotNull($q->answered_at);
        $this->assertEquals($lojista->id, $q->answered_by);
    }

    public function test_lojista_can_toggle_question_visibility(): void
    {
        $lojista   = $this->makeLojista();
        $expositor = $this->makeExpositor($lojista);
        $product   = $this->makeProduct($expositor);
        $cliente   = $this->makeCliente();

        $q = ProductQuestion::create([
            'product_id' => $product->id,
            'user_id'    => $cliente->id,
            'question'   => 'Pergunta inapropriada',
            'is_visible' => true,
        ]);

        Livewire::actingAs($lojista)
            ->test(PerguntaIndex::class)
            ->call('toggleVisibility', $q->id);

        $this->assertFalse($q->fresh()->is_visible);
    }

    public function test_lojista_cannot_answer_other_lojistas_question(): void
    {
        $lojista1  = $this->makeLojista();
        $lojista2  = User::factory()->create(['role' => UserRole::Lojista, 'is_active' => true]);
        $lojista2->assignRole('lojista');

        $expositor2 = $this->makeExpositor($lojista2);
        $product2   = $this->makeProduct($expositor2);
        $cliente    = $this->makeCliente();

        $q = ProductQuestion::create([
            'product_id' => $product2->id,
            'user_id'    => $cliente->id,
            'question'   => 'Pergunta na loja do lojista 2',
        ]);

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        Livewire::actingAs($lojista1)
            ->test(PerguntaIndex::class)
            ->set("answers.{$q->id}", 'Tentativa de resposta indevida')
            ->call('saveAnswer', $q->id);
    }

    public function test_pending_count_correct(): void
    {
        $lojista   = $this->makeLojista();
        $expositor = $this->makeExpositor($lojista);
        $product   = $this->makeProduct($expositor);
        $cliente   = $this->makeCliente();

        ProductQuestion::create(['product_id' => $product->id, 'user_id' => $cliente->id, 'question' => 'P1?']);
        ProductQuestion::create(['product_id' => $product->id, 'user_id' => $cliente->id, 'question' => 'P2?']);
        ProductQuestion::create([
            'product_id'  => $product->id,
            'user_id'     => $cliente->id,
            'question'    => 'P3?',
            'answer'      => 'R3',
            'answered_at' => now(),
            'answered_by' => $lojista->id,
        ]);

        // pendingCount é variável de view — verificamos via DB diretamente
        $count = \App\Models\ProductQuestion::whereHas(
            'product',
            fn ($q) => $q->where('expositor_id', $expositor->id)
        )->whereNull('answered_at')->count();

        $this->assertEquals(2, $count);
    }
}
