<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Livewire\Lojista\Produtos\ProdutoForm;
use App\Livewire\Lojista\Produtos\ProdutoIndex;
use App\Models\Expositor;
use App\Models\Product;
use App\Models\ProductFaq;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * SEC-02 — isolamento do catálogo por expositor.
 *
 * Prova que conhecer o identificador de um item de outra loja não dá acesso
 * nem permite mutação, nas duas superfícies de escrita do catálogo: o
 * formulário Livewire do painel e a API do app mobile.
 *
 * Cada teste negativo confere as duas coisas: que a operação foi negada e que
 * o dado do outro expositor continua exatamente como estava. Negar sem
 * verificar o efeito colateral deixaria passar, por exemplo, um arquivo já
 * apagado antes do 403.
 */
class CatalogoIsolamentoTest extends TestCase
{
    use RefreshDatabase;

    private static int $counter = 0;

    /** @return array{user: User, expositor: Expositor} */
    private function makeLojista(): array
    {
        self::$counter++;

        $user = User::factory()->create([
            'role' => UserRole::Lojista,
            'is_active' => true,
        ]);

        $expositor = Expositor::create([
            'user_id' => $user->id,
            'name' => 'Loja '.self::$counter,
            'slug' => 'loja-'.self::$counter,
            'is_active' => true,
        ]);

        return compact('user', 'expositor');
    }

    private function makeProduct(Expositor $expositor, array $overrides = []): Product
    {
        self::$counter++;

        return Product::create(array_merge([
            'expositor_id' => $expositor->id,
            'item_type' => 'produto',
            'name' => 'Toalha para abajur',
            'slug' => 'toalha-para-abajur-'.self::$counter,
            'description' => 'Peça original do dono.',
            'price' => 49.90,
            'is_active' => true,
            'is_featured' => false,
            'is_digital' => false,
            'has_stock' => true,
            'sort_order' => 0,
        ], $overrides));
    }

    private function withImages(Expositor $expositor): Product
    {
        return $this->makeProduct($expositor, [
            'images' => [
                ['thumb' => 'products/a-thumb.webp', 'medium' => 'products/a-medium.webp'],
                ['thumb' => 'products/b-thumb.webp', 'medium' => 'products/b-medium.webp'],
            ],
        ]);
    }

    /**
     * O importante é que a operação não aconteça. `ProdutoForm` nega com 403,
     * como a API e o CursoBuilder; `ProdutoIndex` nega com o findOrFail
     * escopado, que vira 404 — nenhum dos dois revela nem altera o item alheio.
     */
    private function assertDenied(callable $action): void
    {
        try {
            $action();
        } catch (ModelNotFoundException|HttpException $e) {
            if ($e instanceof HttpException) {
                $this->assertContains($e->getStatusCode(), [403, 404]);
            }

            return;
        }

        $this->fail('A operação deveria ter sido negada, mas foi executada.');
    }

    // ── Casos positivos: o dono continua conseguindo trabalhar ────────────────

    public function test_owner_creates_product_bound_to_own_expositor(): void
    {
        ['user' => $user, 'expositor' => $expositor] = $this->makeLojista();

        Livewire::actingAs($user)
            ->test(ProdutoForm::class)
            ->set('item_type', 'produto')
            ->set('name', 'Tapete de crochê')
            ->set('price', '120.00')
            ->call('save');

        $this->assertDatabaseHas('products', [
            'name' => 'Tapete de crochê',
            'expositor_id' => $expositor->id,
        ]);
    }

    public function test_owner_opens_own_product(): void
    {
        ['user' => $user, 'expositor' => $expositor] = $this->makeLojista();
        $product = $this->makeProduct($expositor);

        $this->actingAs($user)
            ->get(route('lojista.produtos.edit', $product))
            ->assertOk();
    }

    public function test_owner_edits_own_product(): void
    {
        ['user' => $user, 'expositor' => $expositor] = $this->makeLojista();
        $product = $this->makeProduct($expositor);

        Livewire::actingAs($user)
            ->test(ProdutoForm::class, ['product' => $product])
            ->set('name', 'Nome novo do dono')
            ->call('save');

        $this->assertSame('Nome novo do dono', $product->fresh()->name);
    }

    public function test_owner_removes_own_image(): void
    {
        ['user' => $user, 'expositor' => $expositor] = $this->makeLojista();
        $product = $this->withImages($expositor);

        Livewire::actingAs($user)
            ->test(ProdutoForm::class, ['product' => $product])
            ->call('removeImage', 0);

        $this->assertCount(1, $product->fresh()->images);
    }

    public function test_owner_edits_own_faq(): void
    {
        ['user' => $user, 'expositor' => $expositor] = $this->makeLojista();
        $product = $this->makeProduct($expositor);

        Livewire::actingAs($user)
            ->test(ProdutoForm::class, ['product' => $product])
            ->set('faqs', [['question' => 'Qual o material?', 'answer' => 'Algodão informado pelo lojista.']])
            ->call('save');

        $this->assertDatabaseHas('product_faqs', [
            'product_id' => $product->id,
            'question' => 'Qual o material?',
        ]);
    }

    // ── IDOR pela URL de edição ───────────────────────────────────────────────

    public function test_other_lojista_cannot_open_edit_url_of_foreign_product(): void
    {
        ['expositor' => $ownerExpositor] = $this->makeLojista();
        ['user' => $intruder] = $this->makeLojista();

        $product = $this->makeProduct($ownerExpositor);

        $this->actingAs($intruder)
            ->get(route('lojista.produtos.edit', $product))
            ->assertForbidden();
    }

    // ── IDOR montando o componente Livewire direto ────────────────────────────

    public function test_other_lojista_cannot_mount_form_with_foreign_product(): void
    {
        ['expositor' => $ownerExpositor] = $this->makeLojista();
        ['user' => $intruder] = $this->makeLojista();

        $product = $this->makeProduct($ownerExpositor);

        Livewire::actingAs($intruder)
            ->test(ProdutoForm::class, ['product' => $product])
            ->assertForbidden();
    }

    // ── save() manipulado sobre item alheio ───────────────────────────────────

    public function test_other_lojista_cannot_save_foreign_product(): void
    {
        ['expositor' => $ownerExpositor] = $this->makeLojista();
        ['user' => $intruder] = $this->makeLojista();

        $product = $this->makeProduct($ownerExpositor);

        Livewire::actingAs($intruder)
            ->test(ProdutoForm::class, ['product' => $product])
            ->assertForbidden();

        $fresh = $product->fresh();
        $this->assertSame('Toalha para abajur', $fresh->name);
        $this->assertSame('Peça original do dono.', $fresh->description);
    }

    // ── Transferência de propriedade ──────────────────────────────────────────

    public function test_foreign_save_cannot_transfer_ownership(): void
    {
        ['expositor' => $ownerExpositor] = $this->makeLojista();
        ['user' => $intruder, 'expositor' => $intruderExpositor] = $this->makeLojista();

        $product = $this->makeProduct($ownerExpositor);

        Livewire::actingAs($intruder)
            ->test(ProdutoForm::class, ['product' => $product])
            ->assertForbidden();

        $this->assertSame($ownerExpositor->id, $product->fresh()->expositor_id);
        $this->assertNotSame($intruderExpositor->id, $product->fresh()->expositor_id);
    }

    /**
     * Mesmo o dono legítimo salvando não deve reescrever `expositor_id` — ele
     * saiu do payload de update de propósito. Se um dia voltar, este teste cai.
     */
    public function test_ownership_is_immutable_on_legitimate_update(): void
    {
        ['user' => $user, 'expositor' => $expositor] = $this->makeLojista();
        $product = $this->makeProduct($expositor);

        Livewire::actingAs($user)
            ->test(ProdutoForm::class, ['product' => $product])
            ->set('name', 'Editado pelo dono')
            ->call('save');

        $this->assertSame($expositor->id, $product->fresh()->expositor_id);
    }

    // ── Imagem alheia ─────────────────────────────────────────────────────────

    public function test_other_lojista_cannot_remove_foreign_image(): void
    {
        ['expositor' => $ownerExpositor] = $this->makeLojista();
        ['user' => $intruder] = $this->makeLojista();

        $product = $this->withImages($ownerExpositor);

        Livewire::actingAs($intruder)
            ->test(ProdutoForm::class, ['product' => $product])
            ->assertForbidden();

        $this->assertCount(2, $product->fresh()->images);
    }

    // ── FAQ alheia ────────────────────────────────────────────────────────────

    public function test_other_lojista_cannot_change_foreign_faq(): void
    {
        ['expositor' => $ownerExpositor] = $this->makeLojista();
        ['user' => $intruder] = $this->makeLojista();

        $product = $this->makeProduct($ownerExpositor);
        ProductFaq::create([
            'product_id' => $product->id,
            'question' => 'Pergunta do dono',
            'answer' => 'Resposta do dono',
            'sort_order' => 0,
        ]);

        Livewire::actingAs($intruder)
            ->test(ProdutoForm::class, ['product' => $product])
            ->assertForbidden();

        $this->assertDatabaseHas('product_faqs', [
            'product_id' => $product->id,
            'question' => 'Pergunta do dono',
        ]);
        $this->assertSame(1, ProductFaq::where('product_id', $product->id)->count());
    }

    // ── Listagem ──────────────────────────────────────────────────────────────

    public function test_listing_never_shows_foreign_products(): void
    {
        ['user' => $userA, 'expositor' => $expositorA] = $this->makeLojista();
        ['expositor' => $expositorB] = $this->makeLojista();

        $mine = $this->makeProduct($expositorA, ['name' => 'Item da loja A']);
        $theirs = $this->makeProduct($expositorB, ['name' => 'Item da loja B']);

        Livewire::actingAs($userA)
            ->test(ProdutoIndex::class)
            ->assertSee($mine->name)
            ->assertDontSee($theirs->name);
    }

    public function test_other_lojista_cannot_delete_foreign_product_from_listing(): void
    {
        ['expositor' => $ownerExpositor] = $this->makeLojista();
        ['user' => $intruder] = $this->makeLojista();

        $product = $this->makeProduct($ownerExpositor);

        $this->assertDenied(
            fn () => Livewire::actingAs($intruder)
                ->test(ProdutoIndex::class)
                ->call('delete', $product->id)
        );

        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_other_lojista_cannot_toggle_foreign_product(): void
    {
        ['expositor' => $ownerExpositor] = $this->makeLojista();
        ['user' => $intruder] = $this->makeLojista();

        $product = $this->makeProduct($ownerExpositor, ['is_active' => true]);

        $this->assertDenied(
            fn () => Livewire::actingAs($intruder)
                ->test(ProdutoIndex::class)
                ->call('toggleActive', $product->id)
        );

        $this->assertTrue((bool) $product->fresh()->is_active);
    }

    // ── API mobile ────────────────────────────────────────────────────────────

    public function test_api_cannot_show_foreign_product(): void
    {
        ['expositor' => $ownerExpositor] = $this->makeLojista();
        ['user' => $intruder] = $this->makeLojista();

        $product = $this->makeProduct($ownerExpositor);

        Sanctum::actingAs($intruder);
        $this->getJson("/api/v1/lojista/produtos/{$product->id}")->assertStatus(403);
    }

    public function test_api_cannot_update_foreign_product(): void
    {
        ['expositor' => $ownerExpositor] = $this->makeLojista();
        ['user' => $intruder] = $this->makeLojista();

        $product = $this->makeProduct($ownerExpositor);

        Sanctum::actingAs($intruder);
        $this->putJson("/api/v1/lojista/produtos/{$product->id}", [
            'item_type' => 'produto',
            'name' => 'Invadido',
        ])->assertStatus(403);

        $this->assertSame('Toalha para abajur', $product->fresh()->name);
        $this->assertSame($ownerExpositor->id, $product->fresh()->expositor_id);
    }

    public function test_api_cannot_destroy_foreign_product(): void
    {
        ['expositor' => $ownerExpositor] = $this->makeLojista();
        ['user' => $intruder] = $this->makeLojista();

        $product = $this->makeProduct($ownerExpositor);

        Sanctum::actingAs($intruder);
        $this->deleteJson("/api/v1/lojista/produtos/{$product->id}")->assertStatus(403);

        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_api_listing_never_shows_foreign_products(): void
    {
        ['user' => $userA, 'expositor' => $expositorA] = $this->makeLojista();
        ['expositor' => $expositorB] = $this->makeLojista();

        $this->makeProduct($expositorA, ['name' => 'Item da loja A']);
        $this->makeProduct($expositorB, ['name' => 'Item da loja B']);

        Sanctum::actingAs($userA);
        $this->getJson('/api/v1/lojista/produtos')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Item da loja A'])
            ->assertJsonMissing(['name' => 'Item da loja B']);
    }

    // ── Mass assignment: expositor_id vindo do payload não é autoridade ───────

    public function test_api_payload_cannot_choose_owner_on_create(): void
    {
        ['user' => $user, 'expositor' => $expositor] = $this->makeLojista();
        ['expositor' => $foreign] = $this->makeLojista();

        Sanctum::actingAs($user);
        $this->postJson('/api/v1/lojista/produtos', [
            'item_type' => 'produto',
            'name' => 'Tentativa de plantar na loja alheia',
            'expositor_id' => $foreign->id,
        ])->assertCreated();

        $this->assertDatabaseHas('products', [
            'name' => 'Tentativa de plantar na loja alheia',
            'expositor_id' => $expositor->id,
        ]);
        $this->assertDatabaseMissing('products', [
            'name' => 'Tentativa de plantar na loja alheia',
            'expositor_id' => $foreign->id,
        ]);
    }

    public function test_api_payload_cannot_transfer_owner_on_update(): void
    {
        ['user' => $user, 'expositor' => $expositor] = $this->makeLojista();
        ['expositor' => $foreign] = $this->makeLojista();

        $product = $this->makeProduct($expositor);

        Sanctum::actingAs($user);
        $this->putJson("/api/v1/lojista/produtos/{$product->id}", [
            'item_type' => 'produto',
            'name' => 'Renomeado pelo dono',
            'expositor_id' => $foreign->id,
        ])->assertOk();

        $this->assertSame($expositor->id, $product->fresh()->expositor_id);
    }
}
