<?php

namespace Tests\Feature;

use App\Actions\Catalog\SaveProductWithOffer;
use App\Enums\UserRole;
use App\Livewire\Lojista\Produtos\ProdutoForm;
use App\Livewire\Lojista\Produtos\ProdutoIndex;
use App\Models\Expositor;
use App\Models\Product;
use App\Models\ProductOffer;
use App\Models\ProductOfferFaq;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

/**
 * CAT-DOM-02F — o isolamento comercial A × B, provado sobre a oferta.
 *
 * ## Por que o cenário existe se multi-oferta está desabilitada
 *
 * Porque a autorização precisa estar **certa antes** de o mundo deixar de ser
 * 1:1, e não depois. Enquanto cada `Product` tem uma oferta só, autorizar por
 * produto e autorizar por oferta dão a mesma resposta — e um defeito nessa
 * distinção fica invisível até o dia em que dois vendedores oferecem o mesmo
 * item, quando ele já estará em produção.
 *
 * As duas ofertas sobre o mesmo produto são montadas **direto pelas factories**.
 * Isso é fixture estrutural, não ativação: o cadastro continua produzindo uma
 * oferta só, e há teste que falha se isso mudar.
 */
class OfertaIsolamentoComercialTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Storage::fake('public');
    }

    private function lojista(): User
    {
        $user = User::factory()->create(['role' => UserRole::Lojista, 'is_active' => true]);
        $user->assignRole('lojista');

        return $user;
    }

    /**
     * Um produto compartilhado por dois expositores.
     *
     * @return array{produto: Product, userA: User, userB: User, ofertaA: ProductOffer, ofertaB: ProductOffer}
     */
    private function cenarioAxB(): array
    {
        $userA = $this->lojista();
        $userB = $this->lojista();

        $expositorA = Expositor::factory()->create(['user_id' => $userA->id]);
        $expositorB = Expositor::factory()->create(['user_id' => $userB->id]);

        // O produto nasce de A: `products.expositor_id` = A, e é só isso que
        // esse campo significa — quem trouxe o item (D-CAT-11).
        $produto = Product::factory()->create(['expositor_id' => $expositorA->id]);
        $ofertaA = $produto->offers()->sole();

        $ofertaB = ProductOffer::factory()->create([
            'product_id' => $produto->id,
            'expositor_id' => $expositorB->id,
        ]);

        return compact('produto', 'userA', 'userB', 'ofertaA', 'ofertaB');
    }

    /**
     * A operação é recusada — por 403, por 404 ou pela exceção do escopo.
     *
     * O importante é que ela **não aconteça**; qual das três formas de recusa o
     * caminho usa é convenção herdada da SEC-02 e não muda aqui: `ProdutoForm`
     * aborta com 403, os escopos de `ProdutoIndex` negam com `findOrFail`.
     */
    private function assertNegado(callable $tentativa): void
    {
        try {
            $tentativa();
        } catch (ModelNotFoundException|HttpException $recusa) {
            $this->assertTrue(true);

            return;
        }

        $this->fail('A operação deveria ter sido recusada.');
    }

    // ------------------------------------------- a regra única de ownership

    public function test_ownership_comercial_sai_da_oferta_e_de_mais_nada(): void
    {
        ['userA' => $userA, 'userB' => $userB, 'ofertaA' => $ofertaA, 'ofertaB' => $ofertaB] = $this->cenarioAxB();

        $this->assertTrue($ofertaA->pertenceAoExpositorDe($userA));
        $this->assertFalse($ofertaA->pertenceAoExpositorDe($userB));

        $this->assertTrue($ofertaB->pertenceAoExpositorDe($userB));
        $this->assertFalse($ofertaB->pertenceAoExpositorDe($userA));

        // Usuário sem expositor — admin, cliente, visitante — nunca é dono.
        $this->assertFalse($ofertaA->pertenceAoExpositorDe(User::factory()->create()));
        $this->assertFalse($ofertaA->pertenceAoExpositorDe(null));
    }

    /**
     * O teste que desfaz o acoplamento histórico: quem cadastrou o item não
     * ganha nada sobre a oferta de quem o vende.
     */
    public function test_proveniencia_nao_e_ownership(): void
    {
        ['produto' => $produto, 'userA' => $userA, 'userB' => $userB, 'ofertaB' => $ofertaB] = $this->cenarioAxB();

        // A é o `products.expositor_id`; a oferta B é de B.
        $this->assertSame($userA->expositor->id, $produto->expositor_id);

        $this->assertFalse($ofertaB->pertenceAoExpositorDe($userA));
        $this->assertTrue($ofertaB->pertenceAoExpositorDe($userB));
    }

    /**
     * Delegação canônica e ownership comercial são eixos independentes: A pode
     * editar *o que o item é* sem poder tocar na oferta de B, e B é dono da
     * própria oferta sem ganhar autoridade sobre a identidade do item.
     */
    public function test_delegacao_canonica_nao_e_ownership(): void
    {
        ['produto' => $produto, 'userA' => $userA, 'userB' => $userB, 'ofertaB' => $ofertaB] = $this->cenarioAxB();

        $produto->delegarCanonicoPara($userA->expositor->id);
        $produto->refresh();

        $this->assertTrue($produto->delegaCanonicoPara($userA->expositor->id));
        $this->assertFalse($ofertaB->pertenceAoExpositorDe($userA));

        // E o dono da oferta não vira autoridade canônica por sê-lo.
        $this->assertFalse($produto->delegaCanonicoPara($userB->expositor->id));
        $this->assertFalse($userB->can('updateCanonical', $produto));
    }

    // --------------------------------------------------- painel do lojista

    public function test_lojista_nao_monta_o_formulario_da_oferta_alheia(): void
    {
        ['produto' => $produto, 'userB' => $userB] = $this->cenarioAxB();

        // B tem oferta neste produto; a tela carrega a oferta DELE.
        Livewire::actingAs($userB)
            ->test(ProdutoForm::class, ['product' => $produto])
            ->assertOk();

        // Um terceiro sem oferta alguma não passa.
        Livewire::actingAs($this->lojista())
            ->test(ProdutoForm::class, ['product' => $produto])
            ->assertForbidden();
    }

    /**
     * O ponto que o §33 pede: o componente carrega a oferta de quem está
     * autenticado, e não a que o produto sugere. A e B editam a mesma tela e
     * escrevem em ofertas diferentes.
     */
    public function test_cada_lojista_edita_a_propria_oferta_no_mesmo_produto(): void
    {
        ['produto' => $produto, 'userA' => $userA, 'userB' => $userB, 'ofertaA' => $ofertaA, 'ofertaB' => $ofertaB] = $this->cenarioAxB();

        Livewire::actingAs($userA)
            ->test(ProdutoForm::class, ['product' => $produto])
            ->set('price', '10.00')
            ->call('save');

        Livewire::actingAs($userB)
            ->test(ProdutoForm::class, ['product' => $produto])
            ->set('price', '99.00')
            ->call('save');

        $this->assertSame('10.00', $ofertaA->fresh()->price);
        $this->assertSame('99.00', $ofertaB->fresh()->price);
    }

    public function test_faq_e_isolada_por_oferta(): void
    {
        ['produto' => $produto, 'userA' => $userA, 'userB' => $userB, 'ofertaA' => $ofertaA, 'ofertaB' => $ofertaB] = $this->cenarioAxB();

        ProductOfferFaq::create([
            'product_offer_id' => $ofertaB->id,
            'question' => 'FAQ do B',
            'answer' => 'resposta do B',
            'sort_order' => 0,
        ]);

        Livewire::actingAs($userA)
            ->test(ProdutoForm::class, ['product' => $produto])
            ->set('faqs', [['question' => 'FAQ do A', 'answer' => 'resposta do A']])
            ->call('save');

        $this->assertSame(['FAQ do A'], ProductOfferFaq::where('product_offer_id', $ofertaA->id)->pluck('question')->all());
        $this->assertSame(['FAQ do B'], ProductOfferFaq::where('product_offer_id', $ofertaB->id)->pluck('question')->all());
    }

    public function test_imagem_e_isolada_por_oferta(): void
    {
        ['produto' => $produto, 'userA' => $userA, 'ofertaA' => $ofertaA, 'ofertaB' => $ofertaB] = $this->cenarioAxB();

        Storage::disk('public')->put('products/do-b.webp', 'bytes-b');
        $ofertaB->update(['images' => [['thumb' => 'products/do-b.webp', 'medium' => 'products/do-b.webp']]]);

        Livewire::actingAs($userA)
            ->test(ProdutoForm::class, ['product' => $produto])
            ->set('upload1', UploadedFile::fake()->image('do-a.jpg'))
            ->call('save');

        $this->assertNotEmpty($ofertaA->fresh()->images);
        $this->assertSame('products/do-b.webp', $ofertaB->fresh()->images[0]['medium']);
        Storage::disk('public')->assertExists('products/do-b.webp');
    }

    /**
     * Livewire hidrata estado do cliente: o índice recebe um id cru. Trocá-lo
     * pelo id da oferta alheia não pode alcançá-la.
     */
    public function test_lojista_nao_alterna_status_da_oferta_alheia_por_id(): void
    {
        ['userA' => $userA, 'ofertaB' => $ofertaB] = $this->cenarioAxB();

        $ativoAntes = $ofertaB->is_active;

        // O escopo de `ProdutoIndex` é `ProductOffer::where(expositor_id)`, e o
        // `findOrFail` sobre ele não encontra a oferta alheia — em HTTP isso é
        // 404, e dentro do componente a exceção sobe. O que importa é que a
        // escrita não acontece.
        $this->assertNegado(fn () => Livewire::actingAs($userA)
            ->test(ProdutoIndex::class)
            ->call('toggleActive', $ofertaB->id));

        $this->assertSame($ativoAntes, $ofertaB->fresh()->is_active);
    }

    public function test_lojista_nao_remove_a_oferta_alheia_por_id(): void
    {
        ['userA' => $userA, 'ofertaB' => $ofertaB] = $this->cenarioAxB();

        $this->assertNegado(fn () => Livewire::actingAs($userA)
            ->test(ProdutoIndex::class)
            ->call('delete', $ofertaB->id));

        $this->assertDatabaseHas('product_offers', ['id' => $ofertaB->id]);
    }

    public function test_lojista_alterna_o_status_da_propria_oferta(): void
    {
        ['userB' => $userB, 'ofertaB' => $ofertaB] = $this->cenarioAxB();

        Livewire::actingAs($userB)
            ->test(ProdutoIndex::class)
            ->call('toggleActive', $ofertaB->id);

        $this->assertFalse($ofertaB->fresh()->is_active);
    }

    // ----------------------------------------------------------------- API

    public function test_api_do_lojista_so_alcanca_a_propria_oferta(): void
    {
        ['produto' => $produto, 'userA' => $userA, 'ofertaA' => $ofertaA, 'ofertaB' => $ofertaB] = $this->cenarioAxB();

        Sanctum::actingAs($userA);

        $this->putJson(route('api.v1.lojista.produtos.update', $produto), [
            'item_type' => 'produto',
            'name' => $produto->name,
            'price' => 42.0,
        ])->assertOk();

        $this->assertSame('42.00', $ofertaA->fresh()->price);
        $this->assertNotSame('42.00', $ofertaB->fresh()->price);
    }

    public function test_api_nega_produto_em_que_o_lojista_nao_tem_oferta(): void
    {
        ['produto' => $produto] = $this->cenarioAxB();

        Sanctum::actingAs($this->lojista());

        $this->putJson(route('api.v1.lojista.produtos.update', $produto), [
            'item_type' => 'produto',
            'name' => 'Tentativa',
            'price' => 1.0,
        ])->assertForbidden();
    }

    // ------------------------------------------------------------ tampering

    /**
     * O payload não escolhe dono nem produto. `CAMPOS_DA_OFERTA` é allowlist, e
     * nem `expositor_id` nem `product_id` estão nela — então não há como
     * "tomar" a oferta de alguém nem movê-la para outro item pelo request.
     */
    public function test_payload_nao_transfere_a_oferta_nem_a_move_de_produto(): void
    {
        ['produto' => $produto, 'userA' => $userA, 'userB' => $userB, 'ofertaA' => $ofertaA] = $this->cenarioAxB();

        $outroProduto = Product::factory()->create(['expositor_id' => $userB->expositor->id]);

        Sanctum::actingAs($userA);

        $this->putJson(route('api.v1.lojista.produtos.update', $produto), [
            'item_type' => 'produto',
            'name' => $produto->name,
            'price' => 30.0,
            'expositor_id' => $userB->expositor->id,
            'product_id' => $outroProduto->id,
            'product_offer_id' => 999999,
        ])->assertOk();

        $ofertaA->refresh();

        $this->assertSame($userA->expositor->id, $ofertaA->expositor_id);
        $this->assertSame($produto->id, $ofertaA->product_id);
    }

    public function test_formulario_livewire_nao_transfere_a_oferta(): void
    {
        ['produto' => $produto, 'userA' => $userA, 'userB' => $userB, 'ofertaA' => $ofertaA] = $this->cenarioAxB();

        Livewire::actingAs($userA)
            ->test(ProdutoForm::class, ['product' => $produto])
            ->set('price', '55.00')
            ->call('save');

        $ofertaA->refresh();

        $this->assertSame($userA->expositor->id, $ofertaA->expositor_id);
        $this->assertNotSame($userB->expositor->id, $ofertaA->expositor_id);
        $this->assertSame($produto->id, $ofertaA->product_id);
    }

    // ------------------------------------------ canônico ≠ comercial (G-F6)

    public function test_lojista_nao_desliga_o_item_do_catalogo(): void
    {
        ['produto' => $produto, 'userA' => $userA, 'userB' => $userB] = $this->cenarioAxB();

        $this->assertFalse($userA->can('updateStatus', $produto));
        $this->assertFalse($userB->can('updateStatus', $produto));

        // Nem com delegação canônica: a D-CAT-10 tira `is_active` do alcance
        // dela de propósito.
        $produto->delegarCanonicoPara($userA->expositor->id);
        $this->assertFalse($userA->fresh()->can('updateStatus', $produto->fresh()));
    }

    public function test_curadoria_desliga_o_item_do_catalogo(): void
    {
        ['produto' => $produto] = $this->cenarioAxB();

        $curador = User::factory()->create(['role' => UserRole::Editor, 'is_active' => true]);
        $curador->assignRole('supervisor');

        $this->assertTrue($curador->can('updateStatus', $produto));
        $this->assertTrue($curador->can('updateCanonical', $produto));

        // E curadoria não é ownership comercial: ela governa o item, não vende.
        $this->assertFalse($produto->offers()->first()->pertenceAoExpositorDe($curador));
    }

    /** Multi-oferta continua desabilitada: o cadastro produz uma oferta só. */
    public function test_o_cadastro_nao_habilita_multi_oferta(): void
    {
        $userA = $this->lojista();
        $expositor = Expositor::factory()->create(['user_id' => $userA->id]);
        $produto = Product::factory()->create(['expositor_id' => $expositor->id]);

        Livewire::actingAs($userA)
            ->test(ProdutoForm::class, ['product' => $produto])
            ->set('name', 'Revisado')
            ->call('save');

        $this->assertSame(1, $produto->fresh()->offers()->count());
    }

    /**
     * A allowlist é a proteção estrutural: `expositor_id` e `product_id` não
     * podem entrar nela, hoje nem por descuido futuro. É o que torna o
     * tampering acima impossível por construção, e não por vigilância.
     */
    public function test_a_allowlist_da_oferta_nao_expoe_dono_nem_produto(): void
    {
        $this->assertNotContains('expositor_id', SaveProductWithOffer::CAMPOS_DA_OFERTA);
        $this->assertNotContains('product_id', SaveProductWithOffer::CAMPOS_DA_OFERTA);
        $this->assertNotContains('expositor_id', SaveProductWithOffer::CAMPOS_DO_PRODUTO);
    }
}
