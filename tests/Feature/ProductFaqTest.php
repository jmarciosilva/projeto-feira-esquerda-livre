<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Expositor;
use App\Models\Product;
use App\Models\ProductFaq;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductFaqTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function makeLojista(): User
    {
        $user = User::factory()->create(['role' => UserRole::Lojista, 'is_active' => true]);
        $user->assignRole('lojista');
        return $user;
    }

    private function makeExpositor(User $user): Expositor
    {
        return Expositor::create([
            'user_id'     => $user->id,
            'name'        => 'Loja Teste',
            'slug'        => 'loja-teste-' . uniqid(),
            'description' => 'desc',
            'is_active'   => true,
            'is_featured' => false,
        ]);
    }

    private function makeProduct(Expositor $expositor): Product
    {
        return Product::factory()->create([
            'expositor_id' => $expositor->id,
            'item_type'    => 'produto',
            'name'         => 'Produto Teste',
            'slug'         => 'produto-teste-' . uniqid(),
            'is_active'    => true,
            'is_featured'  => false,
            'has_stock'    => true,
            'sort_order'   => 0,
        ]);
    }

    // ── Model & relation ───────────────────────────────────────────────────────

    public function test_product_has_faqs_relation(): void
    {
        $lojista  = $this->makeLojista();
        $expositor = $this->makeExpositor($lojista);
        $product  = $this->makeProduct($expositor);

        ProductFaq::create([
            'product_id' => $product->id,
            'question'   => 'Como funciona?',
            'answer'     => 'Funciona bem.',
            'sort_order' => 0,
        ]);

        $this->assertCount(1, $product->fresh()->faqs);
    }

    public function test_faqs_ordered_by_sort_order(): void
    {
        $lojista   = $this->makeLojista();
        $expositor = $this->makeExpositor($lojista);
        $product   = $this->makeProduct($expositor);

        ProductFaq::create(['product_id' => $product->id, 'question' => 'Segunda', 'answer' => 'R', 'sort_order' => 1]);
        ProductFaq::create(['product_id' => $product->id, 'question' => 'Primeira', 'answer' => 'R', 'sort_order' => 0]);

        $this->assertEquals('Primeira', $product->fresh()->faqs->first()->question);
    }

    // ── Livewire ProdutoForm ───────────────────────────────────────────────────

    public function test_lojista_can_add_and_save_faqs(): void
    {
        $lojista   = $this->makeLojista();
        $expositor = $this->makeExpositor($lojista);
        $product   = $this->makeProduct($expositor);

        Livewire::actingAs($lojista)
            ->test(\App\Livewire\Lojista\Produtos\ProdutoForm::class, ['product' => $product])
            ->call('addFaq')
            ->set('faqs.0.question', 'Qual o prazo de entrega?')
            ->set('faqs.0.answer', 'Em até 7 dias úteis.')
            ->call('save');

        $this->assertDatabaseHas('product_faqs', [
            'product_id' => $product->id,
            'question'   => 'Qual o prazo de entrega?',
            'answer'     => 'Em até 7 dias úteis.',
        ]);
    }

    public function test_empty_faq_items_not_persisted(): void
    {
        $lojista   = $this->makeLojista();
        $expositor = $this->makeExpositor($lojista);
        $product   = $this->makeProduct($expositor);

        Livewire::actingAs($lojista)
            ->test(\App\Livewire\Lojista\Produtos\ProdutoForm::class, ['product' => $product])
            ->call('addFaq')
            ->set('faqs.0.question', '')
            ->set('faqs.0.answer', '')
            ->call('save');

        $this->assertDatabaseMissing('product_faqs', ['product_id' => $product->id]);
    }

    public function test_lojista_can_remove_faq(): void
    {
        $lojista   = $this->makeLojista();
        $expositor = $this->makeExpositor($lojista);
        $product   = $this->makeProduct($expositor);

        Livewire::actingAs($lojista)
            ->test(\App\Livewire\Lojista\Produtos\ProdutoForm::class, ['product' => $product])
            ->call('addFaq')
            ->set('faqs.0.question', 'Pergunta A')
            ->set('faqs.0.answer', 'Resposta A')
            ->call('addFaq')
            ->set('faqs.1.question', 'Pergunta B')
            ->set('faqs.1.answer', 'Resposta B')
            ->call('removeFaq', 0)
            ->assertSet('faqs', [['question' => 'Pergunta B', 'answer' => 'Resposta B']]);
    }

    public function test_faqs_loaded_on_edit(): void
    {
        $lojista   = $this->makeLojista();
        $expositor = $this->makeExpositor($lojista);
        $product   = $this->makeProduct($expositor);

        ProductFaq::create([
            'product_id' => $product->id,
            'question'   => 'Tem garantia?',
            'answer'     => 'Sim, 30 dias.',
            'sort_order' => 0,
        ]);

        Livewire::actingAs($lojista)
            ->test(\App\Livewire\Lojista\Produtos\ProdutoForm::class, ['product' => $product])
            ->assertSet('faqs.0.question', 'Tem garantia?')
            ->assertSet('faqs.0.answer', 'Sim, 30 dias.');
    }

    public function test_max_15_faqs(): void
    {
        $lojista   = $this->makeLojista();
        $expositor = $this->makeExpositor($lojista);
        $product   = $this->makeProduct($expositor);

        $component = Livewire::actingAs($lojista)
            ->test(\App\Livewire\Lojista\Produtos\ProdutoForm::class, ['product' => $product]);

        for ($i = 0; $i < 16; $i++) {
            $component->call('addFaq');
        }

        $component->assertSet('faqs', fn ($faqs) => count($faqs) === 15);
    }

    // ── Página pública ─────────────────────────────────────────────────────────

    public function test_faq_displayed_on_product_page(): void
    {
        $lojista   = $this->makeLojista();
        $expositor = $this->makeExpositor($lojista);
        $product   = $this->makeProduct($expositor);

        ProductFaq::create([
            'product_id' => $product->id,
            'question'   => 'Aceita troca?',
            'answer'     => 'Sim, em 7 dias.',
            'sort_order' => 0,
        ]);

        $this->get(route('loja.produto', [$expositor->slug, $product->slug]))
             ->assertOk()
             ->assertSee('Perguntas Frequentes')
             ->assertSee('Aceita troca?')
             ->assertSee('Sim, em 7 dias.');
    }

    public function test_faq_section_hidden_when_empty(): void
    {
        $lojista   = $this->makeLojista();
        $expositor = $this->makeExpositor($lojista);
        $product   = $this->makeProduct($expositor);

        $this->get(route('loja.produto', [$expositor->slug, $product->slug]))
             ->assertOk()
             ->assertDontSee('Perguntas Frequentes');
    }

    public function test_save_replaces_existing_faqs(): void
    {
        $lojista   = $this->makeLojista();
        $expositor = $this->makeExpositor($lojista);
        $product   = $this->makeProduct($expositor);

        ProductFaq::create([
            'product_id' => $product->id,
            'question'   => 'Antiga pergunta',
            'answer'     => 'Antiga resposta',
            'sort_order' => 0,
        ]);

        Livewire::actingAs($lojista)
            ->test(\App\Livewire\Lojista\Produtos\ProdutoForm::class, ['product' => $product])
            ->set('faqs.0.question', 'Nova pergunta')
            ->set('faqs.0.answer', 'Nova resposta')
            ->call('save');

        $this->assertDatabaseMissing('product_faqs', ['question' => 'Antiga pergunta']);
        $this->assertDatabaseHas('product_faqs', ['question' => 'Nova pergunta']);
    }
}
