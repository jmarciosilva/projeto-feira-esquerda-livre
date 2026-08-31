<?php

namespace Tests\Feature;

use App\Actions\Catalog\Contexto;
use App\Actions\Catalog\ResolveProductOffer;
use App\Enums\UserRole;
use App\Livewire\Lojista\Produtos\ProdutoForm;
use App\Models\Expositor;
use App\Models\Product;
use App\Models\ProductOffer;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * CAT-DOM-02G · G-9 — a oferta é escolhida, nunca adivinhada.
 *
 * ## O que estes testes protegem
 *
 * Enquanto cada produto tem uma oferta só, `first()`, `orderBy('id')` e
 * `ofertaVigente` dão todos a resposta certa. O defeito só aparece com o
 * segundo vendedor — e aí já está em produção, decidindo de quem o cliente
 * compra por um critério que ninguém aprovou.
 *
 * Os cenários com duas ofertas são montados **direto pelas factories**. É
 * fixture estrutural, não ativação: o cadastro continua produzindo uma oferta
 * só, e há teste aqui que falha se isso mudar.
 */
class OfertaSelecaoExplicitaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function resolver(): ResolveProductOffer
    {
        return app(ResolveProductOffer::class);
    }

    private function expositor(): Expositor
    {
        return Expositor::factory()->create(['user_id' => User::factory()->create()->id]);
    }

    private function produtoComUmaOferta(): Product
    {
        return Product::factory()->create(['expositor_id' => $this->expositor()->id]);
    }

    private function segundaOferta(Product $produto): ProductOffer
    {
        return ProductOffer::factory()->create([
            'product_id' => $produto->id,
            'expositor_id' => $this->expositor()->id,
        ]);
    }

    // ------------------------------------------------------------- o seletor

    public function test_uma_oferta_resolve_sem_id_explicito(): void
    {
        $produto = $this->produtoComUmaOferta();

        $this->assertSame(
            $produto->offers()->sole()->id,
            ($this->resolver())($produto)?->id,
        );
    }

    public function test_zero_ofertas_nao_resolve(): void
    {
        $produto = Product::factory()->semOferta()->create();

        $this->assertNull(($this->resolver())($produto));
    }

    /** O caso central: nada de primeira, mais barata, mais antiga ou mais nova. */
    public function test_duas_ofertas_sem_id_nao_resolvem(): void
    {
        $produto = $this->produtoComUmaOferta();
        $this->segundaOferta($produto);

        $this->assertNull(($this->resolver())($produto));
    }

    public function test_id_explicito_resolve_a_oferta_pedida(): void
    {
        $produto = $this->produtoComUmaOferta();
        $ofertaB = $this->segundaOferta($produto);

        $this->assertSame($ofertaB->id, ($this->resolver())($produto, $ofertaB->id)?->id);
        $this->assertSame(
            $produto->offers()->orderBy('id')->first()->id,
            ($this->resolver())($produto, $produto->offers()->orderBy('id')->first()->id)?->id,
        );
    }

    /** Oferta de outro produto: recusa, e não substituição silenciosa. */
    public function test_oferta_de_outro_produto_nao_resolve(): void
    {
        $p1 = $this->produtoComUmaOferta();
        $p2 = $this->produtoComUmaOferta();

        $this->assertNull(($this->resolver())($p1, $p2->offers()->sole()->id));
    }

    public function test_oferta_inexistente_nao_resolve(): void
    {
        $this->assertNull(($this->resolver())($this->produtoComUmaOferta(), 999999));
    }

    /**
     * A regra que impede o *buy box* acidental: com preços diferentes, o
     * seletor continua recusando. Se algum dia alguém trocá-lo por
     * `ofertaVigente`, este teste falha.
     */
    public function test_nunca_escolhe_pela_mais_barata(): void
    {
        $produto = $this->produtoComUmaOferta();
        $produto->offers()->sole()->update(['price' => 500]);
        $this->segundaOferta($produto)->update(['price' => 10]);

        $this->assertNotNull($produto->fresh()->ofertaVigente);
        $this->assertNull(($this->resolver())($produto));
    }

    // ------------------------------------------------------------- contexto

    public function test_compra_exige_oferta_vigente(): void
    {
        $produto = $this->produtoComUmaOferta();
        $produto->offers()->sole()->update(['is_active' => false]);

        $this->assertNull(($this->resolver())($produto, null, Contexto::Compra));
    }

    /**
     * Histórico aceita oferta inativa: pedido e matrícula apontam para o que
     * foi vendido, não para o que ainda está à venda (D-02G-4).
     */
    public function test_historico_aceita_oferta_inativa(): void
    {
        $produto = $this->produtoComUmaOferta();
        $oferta = $produto->offers()->sole();
        $oferta->update(['is_active' => false]);

        $this->assertSame($oferta->id, ($this->resolver())($produto, $oferta->id, Contexto::Historico)?->id);
    }

    /**
     * E o histórico **não** troca a oferta inativa pela ativa só porque ela
     * ficou sendo a única vigente.
     */
    public function test_historico_nao_migra_para_a_oferta_que_sobrou_ativa(): void
    {
        $produto = $this->produtoComUmaOferta();
        $comprada = $produto->offers()->sole();
        $outra = $this->segundaOferta($produto);

        $comprada->update(['is_active' => false]);

        $this->assertSame($comprada->id, ($this->resolver())($produto, $comprada->id, Contexto::Historico)?->id);
        $this->assertNotSame($outra->id, ($this->resolver())($produto, $comprada->id, Contexto::Historico)?->id);
    }

    // -------------------------------------------------- carrinho da API

    public function test_carrinho_aceita_produto_de_oferta_unica_sem_campo_novo(): void
    {
        $produto = $this->produtoComUmaOferta();

        Sanctum::actingAs(User::factory()->create());

        $this->postJson(route('api.v1.carrinho.itens.store'), ['product_id' => $produto->id])
            ->assertCreated();

        $this->assertDatabaseHas('cart_items', [
            'product_offer_id' => $produto->offers()->sole()->id,
        ]);
    }

    public function test_carrinho_com_duas_ofertas_exige_escolha(): void
    {
        $produto = $this->produtoComUmaOferta();
        $this->segundaOferta($produto);

        Sanctum::actingAs(User::factory()->create());

        $this->postJson(route('api.v1.carrinho.itens.store'), ['product_id' => $produto->id])
            ->assertStatus(422)
            ->assertJsonValidationErrors('product_offer_id');

        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_carrinho_respeita_a_oferta_escolhida(): void
    {
        $produto = $this->produtoComUmaOferta();
        $ofertaB = $this->segundaOferta($produto);

        Sanctum::actingAs(User::factory()->create());

        $this->postJson(route('api.v1.carrinho.itens.store'), [
            'product_id' => $produto->id,
            'product_offer_id' => $ofertaB->id,
        ])->assertCreated();

        $this->assertDatabaseHas('cart_items', [
            'product_offer_id' => $ofertaB->id,
            'expositor_id' => $ofertaB->expositor_id,
        ]);
    }

    public function test_carrinho_recusa_oferta_de_outro_produto(): void
    {
        $p1 = $this->produtoComUmaOferta();
        $p2 = $this->produtoComUmaOferta();

        Sanctum::actingAs(User::factory()->create());

        $this->postJson(route('api.v1.carrinho.itens.store'), [
            'product_id' => $p1->id,
            'product_offer_id' => $p2->offers()->sole()->id,
        ])->assertStatus(422)->assertJsonValidationErrors('product_offer_id');

        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_carrinho_recusa_produto_sem_oferta_vigente(): void
    {
        $produto = $this->produtoComUmaOferta();
        $produto->offers()->sole()->update(['is_active' => false]);

        Sanctum::actingAs(User::factory()->create());

        $this->postJson(route('api.v1.carrinho.itens.store'), ['product_id' => $produto->id])
            ->assertStatus(422);

        $this->assertDatabaseCount('cart_items', 0);
    }

    // ------------------------------------------------- multi-oferta bloqueada

    /**
     * O teste de **não ativação**: a 02G prepara a arquitetura e não abre a
     * porta. O cadastro normal continua produzindo uma oferta por produto.
     */
    public function test_o_cadastro_continua_produzindo_uma_unica_oferta(): void
    {
        $user = User::factory()->create(['role' => UserRole::Lojista, 'is_active' => true]);
        $user->assignRole('lojista');
        $expositor = Expositor::factory()->create(['user_id' => $user->id]);
        $produto = Product::factory()->create(['expositor_id' => $expositor->id]);

        Livewire::actingAs($user)
            ->test(ProdutoForm::class, ['product' => $produto])
            ->set('name', 'Revisado')
            ->call('save');

        $this->assertSame(1, $produto->fresh()->offers()->count());
    }

    /**
     * E um lojista sem oferta sobre o item não consegue criar a segunda pela
     * tela — continua sendo recusado pelo guard da CAT-DOM-02F.
     */
    public function test_terceiro_nao_cria_segunda_oferta_pelo_formulario(): void
    {
        $produto = $this->produtoComUmaOferta();

        $intruso = User::factory()->create(['role' => UserRole::Lojista, 'is_active' => true]);
        $intruso->assignRole('lojista');
        Expositor::factory()->create(['user_id' => $intruso->id]);

        Livewire::actingAs($intruso)
            ->test(ProdutoForm::class, ['product' => $produto])
            ->assertForbidden();

        $this->assertSame(1, $produto->fresh()->offers()->count());
    }
}
