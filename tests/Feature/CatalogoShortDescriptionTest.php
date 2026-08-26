<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Livewire\Lojista\Produtos\ProdutoForm;
use App\Models\Expositor;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * CAT-02 — `short_description` como campo real do domínio de catálogo.
 *
 * O resumo curto é opcional por decisão: existem itens cadastrados antes dele e
 * nenhum deles passa a ser inválido. Por isso quase todo teste aqui tem um par
 * — o caminho com resumo e o caminho sem — e os dois precisam continuar
 * valendo.
 */
class CatalogoShortDescriptionTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{user: User, expositor: Expositor} */
    private function makeLojista(): array
    {
        $user = User::factory()->create(['role' => UserRole::Lojista, 'is_active' => true]);
        $expositor = Expositor::factory()->create(['user_id' => $user->id]);

        return compact('user', 'expositor');
    }

    // ── Schema ────────────────────────────────────────────────────────────────

    public function test_products_table_has_short_description_column(): void
    {
        $this->assertTrue(Schema::hasColumn('products', 'short_description'));
    }

    public function test_short_description_is_nullable(): void
    {
        $product = Product::factory()->create(['short_description' => null]);

        $this->assertNull($product->fresh()->short_description);
    }

    public function test_description_and_short_description_coexist(): void
    {
        $product = Product::factory()->create([
            'short_description' => 'Resumo curto.',
            'description' => 'Texto longo, com muito mais detalhe do que o resumo.',
        ]);

        $fresh = $product->fresh();
        $this->assertSame('Resumo curto.', $fresh->short_description);
        $this->assertSame('Texto longo, com muito mais detalhe do que o resumo.', $fresh->description);
    }

    // ── Model ─────────────────────────────────────────────────────────────────

    public function test_model_persists_short_description(): void
    {
        $product = Product::factory()->comResumo('Peça artesanal em crochê.')->create();

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'short_description' => 'Peça artesanal em crochê.',
        ]);
    }

    public function test_short_description_is_fillable(): void
    {
        $this->assertContains('short_description', (new Product)->getFillable());
    }

    // ── Livewire: criação e edição ────────────────────────────────────────────

    public function test_create_with_short_description(): void
    {
        ['user' => $user, 'expositor' => $expositor] = $this->makeLojista();

        Livewire::actingAs($user)
            ->test(ProdutoForm::class)
            ->set('item_type', 'produto')
            ->set('name', 'Toalha para abajur')
            ->set('short_description', 'Peça artesanal em crochê para decoração de abajures.')
            ->set('description', 'Descrição completa da peça.')
            ->call('save');

        $this->assertDatabaseHas('products', [
            'name' => 'Toalha para abajur',
            'expositor_id' => $expositor->id,
            'short_description' => 'Peça artesanal em crochê para decoração de abajures.',
        ]);
    }

    public function test_create_without_short_description_stores_null(): void
    {
        ['user' => $user] = $this->makeLojista();

        Livewire::actingAs($user)
            ->test(ProdutoForm::class)
            ->set('item_type', 'produto')
            ->set('name', 'Sem resumo')
            ->call('save');

        $this->assertSame(null, Product::where('name', 'Sem resumo')->first()->short_description);
    }

    public function test_edit_updates_short_description(): void
    {
        ['user' => $user, 'expositor' => $expositor] = $this->makeLojista();
        $product = Product::factory()->doExpositor($expositor)->comResumo('Resumo antigo.')->create();

        Livewire::actingAs($user)
            ->test(ProdutoForm::class, ['product' => $product])
            ->set('short_description', 'Resumo novo.')
            ->call('save');

        $this->assertSame('Resumo novo.', $product->fresh()->short_description);
    }

    public function test_mount_loads_existing_short_description(): void
    {
        ['user' => $user, 'expositor' => $expositor] = $this->makeLojista();
        $product = Product::factory()->doExpositor($expositor)->comResumo('Já preenchido.')->create();

        Livewire::actingAs($user)
            ->test(ProdutoForm::class, ['product' => $product])
            ->assertSet('short_description', 'Já preenchido.');
    }

    /** Item anterior à CAT-02: sem resumo, o formulário abre e salva normalmente. */
    public function test_legacy_product_without_short_description_still_editable(): void
    {
        ['user' => $user, 'expositor' => $expositor] = $this->makeLojista();
        $product = Product::factory()->doExpositor($expositor)->create(['short_description' => null]);

        Livewire::actingAs($user)
            ->test(ProdutoForm::class, ['product' => $product])
            ->assertSet('short_description', '')
            ->set('name', 'Renomeado sem resumo')
            ->call('save');

        $fresh = $product->fresh();
        $this->assertSame('Renomeado sem resumo', $fresh->name);
        $this->assertNull($fresh->short_description);
    }

    public function test_short_description_over_limit_is_rejected(): void
    {
        ['user' => $user] = $this->makeLojista();

        Livewire::actingAs($user)
            ->test(ProdutoForm::class)
            ->set('item_type', 'produto')
            ->set('name', 'Resumo gigante')
            ->set('short_description', str_repeat('a', 501))
            ->call('save')
            ->assertHasErrors(['short_description']);

        $this->assertDatabaseMissing('products', ['name' => 'Resumo gigante']);
    }

    public function test_short_description_at_limit_is_accepted(): void
    {
        ['user' => $user] = $this->makeLojista();

        Livewire::actingAs($user)
            ->test(ProdutoForm::class)
            ->set('item_type', 'produto')
            ->set('name', 'Resumo no limite')
            ->set('short_description', str_repeat('a', 500))
            ->call('save')
            ->assertHasNoErrors(['short_description']);

        $this->assertDatabaseHas('products', ['name' => 'Resumo no limite']);
    }

    // ── Os três eixos ─────────────────────────────────────────────────────────

    public function test_short_description_works_for_all_item_types(): void
    {
        ['user' => $user, 'expositor' => $expositor] = $this->makeLojista();

        foreach (['produto', 'servico', 'cuidado'] as $tipo) {
            Livewire::actingAs($user)
                ->test(ProdutoForm::class)
                ->set('item_type', $tipo)
                ->set('name', 'Item '.$tipo)
                ->set('short_description', 'Resumo de '.$tipo)
                ->call('save');

            $this->assertDatabaseHas('products', [
                'item_type' => $tipo,
                'expositor_id' => $expositor->id,
                'short_description' => 'Resumo de '.$tipo,
            ]);
        }
    }

    // ── Regressão SEC-02: o campo novo não abre brecha lateral ────────────────

    public function test_other_lojista_cannot_change_foreign_short_description(): void
    {
        ['expositor' => $ownerExpositor] = $this->makeLojista();
        ['user' => $intruder] = $this->makeLojista();

        $product = Product::factory()
            ->doExpositor($ownerExpositor)
            ->comResumo('Resumo do dono.')
            ->create();

        Livewire::actingAs($intruder)
            ->test(ProdutoForm::class, ['product' => $product])
            ->assertForbidden();

        $fresh = $product->fresh();
        $this->assertSame('Resumo do dono.', $fresh->short_description);
        $this->assertSame($ownerExpositor->id, $fresh->expositor_id);
    }

    public function test_api_cannot_change_foreign_short_description(): void
    {
        ['expositor' => $ownerExpositor] = $this->makeLojista();
        ['user' => $intruder] = $this->makeLojista();

        $product = Product::factory()
            ->doExpositor($ownerExpositor)
            ->comResumo('Resumo do dono.')
            ->create();

        Sanctum::actingAs($intruder);
        $this->putJson("/api/v1/lojista/produtos/{$product->id}", [
            'item_type' => 'produto',
            'name' => $product->name,
            'short_description' => 'Invadido',
        ])->assertStatus(403);

        $this->assertSame('Resumo do dono.', $product->fresh()->short_description);
    }

    // ── API ───────────────────────────────────────────────────────────────────

    public function test_api_store_accepts_short_description(): void
    {
        ['user' => $user, 'expositor' => $expositor] = $this->makeLojista();

        Sanctum::actingAs($user);
        $this->postJson('/api/v1/lojista/produtos', [
            'item_type' => 'produto',
            'name' => 'Tapete de crochê',
            'short_description' => 'Tapete artesanal feito à mão.',
        ])->assertCreated()
            ->assertJsonPath('data.short_description', 'Tapete artesanal feito à mão.');

        $this->assertDatabaseHas('products', [
            'expositor_id' => $expositor->id,
            'short_description' => 'Tapete artesanal feito à mão.',
        ]);
    }

    public function test_api_show_returns_short_description(): void
    {
        ['user' => $user, 'expositor' => $expositor] = $this->makeLojista();
        $product = Product::factory()->doExpositor($expositor)->comResumo('Resumo exposto.')->create();

        Sanctum::actingAs($user);
        $this->getJson("/api/v1/lojista/produtos/{$product->id}")
            ->assertOk()
            ->assertJsonPath('data.short_description', 'Resumo exposto.');
    }

    public function test_api_update_changes_short_description(): void
    {
        ['user' => $user, 'expositor' => $expositor] = $this->makeLojista();
        $product = Product::factory()->doExpositor($expositor)->comResumo('Antes.')->create();

        Sanctum::actingAs($user);
        $this->putJson("/api/v1/lojista/produtos/{$product->id}", [
            'item_type' => 'produto',
            'name' => $product->name,
            'short_description' => 'Depois.',
        ])->assertOk();

        $this->assertSame('Depois.', $product->fresh()->short_description);
    }

    public function test_api_rejects_short_description_over_limit(): void
    {
        ['user' => $user] = $this->makeLojista();

        Sanctum::actingAs($user);
        $this->postJson('/api/v1/lojista/produtos', [
            'item_type' => 'produto',
            'name' => 'Resumo gigante',
            'short_description' => str_repeat('a', 501),
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['short_description']);
    }

    /**
     * Compatibilidade: cliente antigo, que não conhece o campo, continua
     * criando item e continua recebendo `description` como sempre.
     */
    public function test_api_remains_backward_compatible(): void
    {
        ['user' => $user] = $this->makeLojista();

        Sanctum::actingAs($user);
        $response = $this->postJson('/api/v1/lojista/produtos', [
            'item_type' => 'produto',
            'name' => 'Cliente antigo',
            'description' => 'Só a descrição de sempre.',
        ])->assertCreated();

        $response->assertJsonPath('data.description', 'Só a descrição de sempre.');
        $response->assertJsonPath('data.short_description', null);
    }

    // ── Catálogo público ──────────────────────────────────────────────────────

    public function test_product_page_meta_prefers_short_description(): void
    {
        ['expositor' => $expositor] = $this->makeLojista();
        $product = Product::factory()->doExpositor($expositor)->create([
            'short_description' => 'Resumo escrito para ser lido fora da página.',
            'description' => 'Descrição longa que hoje seria cortada no caractere 160.',
        ]);

        $this->get(route('loja.produto', [$expositor->slug, $product->slug]))
            ->assertOk()
            ->assertSee('Resumo escrito para ser lido fora da página.', false);
    }

    public function test_product_page_falls_back_to_description_without_short(): void
    {
        ['expositor' => $expositor] = $this->makeLojista();
        $product = Product::factory()->doExpositor($expositor)->create([
            'short_description' => null,
            'description' => 'Descricao longa usada como meta quando nao ha resumo.',
        ]);

        $this->get(route('loja.produto', [$expositor->slug, $product->slug]))
            ->assertOk()
            ->assertSee('Descricao longa usada como meta quando nao ha resumo.', false);
    }
}
