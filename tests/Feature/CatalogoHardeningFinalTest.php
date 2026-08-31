<?php

namespace Tests\Feature;

use App\Actions\Catalog\Contexto;
use App\Actions\Catalog\ResolveProductOffer;
use App\Actions\Catalog\SaveProductWithOffer;
use App\Enums\UserRole;
use App\Models\Expositor;
use App\Models\Product;
use App\Models\ProductOffer;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * CAT-DOM-02I — os invariantes transversais da fundação, num lugar só.
 *
 * ## O que este arquivo é, e o que ele não é
 *
 * Ele **não** reprova o que as fases já provaram. Isolamento A × B, autoridade
 * de resposta, projeção exata da FAQ, seleção de oferta, origem da matrícula e
 * remoção das colunas legadas têm suítes próprias, e continuam sendo a prova
 * detalhada de cada uma.
 *
 * O que este arquivo faz é diferente: fixa, em asserções curtas, as **fronteiras
 * que atravessam a trilha inteira** — as que uma fase futura poderia desfazer
 * sem tocar em nenhuma suíte específica. É o teste que falha quando alguém
 * reintroduz um espelho comercial, faz `products.expositor_id` autorizar de
 * novo, ou abre uma porta para multi-oferta sem decisão.
 *
 * ```text
 * Product                          ProductOffer
 * ├── identidade / curadoria       ├── vendedor
 * ├── slug, categoria, descrição   ├── preço, estoque, logística
 * ├── imagem canônica              ├── status comercial
 * └── proveniência, delegação      ├── imagem comercial
 *                                  └── FAQ comercial
 * ```
 */
class CatalogoHardeningFinalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    private function lojista(): User
    {
        $user = User::factory()->create(['role' => UserRole::Lojista, 'is_active' => true]);
        $user->assignRole('lojista');

        return $user;
    }

    private function expositor(?User $user = null): Expositor
    {
        return Expositor::factory()->create(['user_id' => ($user ?? $this->lojista())->id]);
    }

    // ─────────────────────────────────────────────── I-1 · schema consolidado

    /**
     * `products` guarda identidade; `product_offers` guarda comércio. Se algum
     * dos doze reaparecer no produto, o espelho voltou — e com ele o problema
     * que a trilha inteira existiu para resolver.
     */
    public function test_a_fronteira_entre_canonico_e_comercial_esta_no_schema(): void
    {
        foreach (SaveProductWithOffer::ESPELHOS_COMERCIAIS_LEGADOS as $campo) {
            $this->assertFalse(Schema::hasColumn('products', $campo), "products.{$campo} reapareceu.");
            $this->assertTrue(Schema::hasColumn('product_offers', $campo), "product_offers.{$campo} sumiu.");
        }

        // O que é do item continua no item.
        foreach (['name', 'slug', 'description', 'item_type', 'category_id', 'is_digital', 'is_active', 'images', 'image_path', 'expositor_id', 'canonical_delegate_expositor_id'] as $campo) {
            $this->assertTrue(Schema::hasColumn('products', $campo), "products.{$campo} foi removida por engano.");
        }

        // E o conteúdo comercial mora onde a 02D o pôs.
        $this->assertTrue(Schema::hasColumn('product_offers', 'images'));
        $this->assertTrue(Schema::hasColumn('product_offer_faqs', 'product_offer_id'));
        $this->assertTrue(Schema::hasColumn('product_questions', 'product_offer_id'));
    }

    // ──────────────────────────────────────── I-2 · I-3 · nada escreve o legado

    public function test_o_model_nao_reintroduz_os_espelhos(): void
    {
        $product = new Product;

        foreach (SaveProductWithOffer::ESPELHOS_COMERCIAIS_LEGADOS as $campo) {
            $this->assertNotContains($campo, $product->getFillable());
            $this->assertArrayNotHasKey($campo, $product->getCasts());
            $this->assertNotContains($campo, SaveProductWithOffer::CAMPOS_DO_PRODUTO);
            $this->assertContains($campo, SaveProductWithOffer::CAMPOS_DA_OFERTA);
        }
    }

    /**
     * A allowlist é a barreira estrutural: sem `expositor_id` e `product_id`
     * nela, nenhum payload toma uma oferta nem a move de item.
     */
    public function test_a_oferta_nao_muda_de_dono_nem_de_item_por_payload(): void
    {
        $this->assertNotContains('expositor_id', SaveProductWithOffer::CAMPOS_DA_OFERTA);
        $this->assertNotContains('product_id', SaveProductWithOffer::CAMPOS_DA_OFERTA);
        $this->assertNotContains('expositor_id', SaveProductWithOffer::CAMPOS_DO_PRODUTO);
    }

    // ────────────────────────────────────────────── I-4 · autorização separada

    /**
     * Os dois eixos, nos dois sentidos: proveniência e delegação não dão
     * oferta; oferta não dá autoridade canônica.
     */
    public function test_proveniencia_e_delegacao_nao_sao_ownership_comercial(): void
    {
        $userA = $this->lojista();
        $userB = $this->lojista();
        $expositorA = $this->expositor($userA);
        $expositorB = $this->expositor($userB);

        // A trouxe o item ao catálogo e recebeu a delegação.
        $produto = Product::factory()->create(['expositor_id' => $expositorA->id]);
        $produto->delegarCanonicoPara($expositorA->id);
        $produto->refresh();

        $ofertaB = ProductOffer::factory()->create([
            'product_id' => $produto->id,
            'expositor_id' => $expositorB->id,
        ]);

        // Proveniência e delegação de A não alcançam a oferta de B.
        $this->assertSame($expositorA->id, $produto->expositor_id);
        $this->assertTrue($produto->delegaCanonicoPara($expositorA->id));
        $this->assertFalse($ofertaB->pertenceAoExpositorDe($userA));

        // E a oferta de B não vira autoridade sobre o item.
        $this->assertTrue($ofertaB->pertenceAoExpositorDe($userB));
        $this->assertFalse($userB->can('updateCanonical', $produto));
        $this->assertFalse($userB->can('updateStatus', $produto));

        // Revogar a delegação encerra a autoridade — sem tocar na oferta.
        $produto->revogarDelegacaoCanonica();
        $this->assertFalse($userA->fresh()->can('updateCanonical', $produto->fresh()));
        $this->assertDatabaseHas('product_offers', ['id' => $ofertaB->id]);
    }

    // ──────────────────────────── I-4 · Product.is_active × Offer.is_active

    /**
     * Os dois status respondem perguntas diferentes, e a vitrine exige as duas
     * respostas: o item vale no catálogo **e** a loja o está oferecendo.
     */
    public function test_os_dois_status_sao_conceitos_distintos(): void
    {
        $expositor = $this->expositor();

        $ativoAtiva = Product::factory()->create(['expositor_id' => $expositor->id, 'is_active' => true]);

        $ativoInativa = Product::factory()->create(['expositor_id' => $expositor->id, 'is_active' => true]);
        $ativoInativa->offers()->sole()->update(['is_active' => false]);

        $inativoAtiva = Product::factory()->create(['expositor_id' => $expositor->id, 'is_active' => false]);

        $vigentes = Product::query()->comOfertaVigente()->pluck('id')->all();

        $this->assertContains($ativoAtiva->id, $vigentes);
        $this->assertNotContains($ativoInativa->id, $vigentes, 'Oferta recolhida continuou na vitrine.');
        $this->assertNotContains($inativoAtiva->id, $vigentes, 'Item invalidado pela curadoria continuou na vitrine.');

        // E o lojista não alcança o interruptor canônico.
        $this->assertFalse($expositor->user->can('updateStatus', $ativoAtiva));
    }

    // ───────────────────────────────────────── I-7 · seleção determinística

    public function test_a_selecao_de_oferta_nunca_e_adivinhada(): void
    {
        $resolver = app(ResolveProductOffer::class);

        $semOferta = Product::factory()->semOferta()->create();
        $this->assertNull($resolver($semOferta), '0 ofertas resolveu alguma coisa.');

        $umaOferta = Product::factory()->create(['expositor_id' => $this->expositor()->id]);
        $this->assertSame($umaOferta->offers()->sole()->id, $resolver($umaOferta)?->id);

        // Duas ofertas com preços muito diferentes: nem a mais barata, nem a
        // primeira, nem a mais recente. Sem contexto, não resolve.
        $ofertaA = $umaOferta->offers()->sole();
        $ofertaA->update(['price' => 900]);
        $ofertaB = ProductOffer::factory()->create([
            'product_id' => $umaOferta->id,
            'expositor_id' => $this->expositor()->id,
            'price' => 9,
        ]);

        $this->assertNull($resolver($umaOferta->fresh()), 'Duas ofertas resolveram sem contexto.');
        $this->assertSame($ofertaB->id, $resolver($umaOferta->fresh(), $ofertaB->id)?->id);
        $this->assertSame($ofertaA->id, $resolver($umaOferta->fresh(), $ofertaA->id)?->id);

        // Oferta de outro item: recusa, nunca substituição.
        $outro = Product::factory()->create(['expositor_id' => $this->expositor()->id]);
        $this->assertNull($resolver($outro, $ofertaB->id));
    }

    /** Comprar exige oferta vigente; olhar para trás, não. */
    public function test_historico_nao_e_resolvido_pelo_estado_atual(): void
    {
        $resolver = app(ResolveProductOffer::class);

        $produto = Product::factory()->create(['expositor_id' => $this->expositor()->id]);
        $comprada = $produto->offers()->sole();
        $comprada->update(['is_active' => false]);

        $this->assertNull($resolver($produto->fresh(), $comprada->id, Contexto::Compra));
        $this->assertSame($comprada->id, $resolver($produto->fresh(), $comprada->id, Contexto::Historico)?->id);
    }

    // ───────────────────────────────────────── I-11 · multi-oferta desabilitada

    /**
     * O gate mais importante da fase: a arquitetura suporta 1:N, e o produto
     * não o permite.
     *
     * A prova é estrutural, não de vigilância — `SaveProductWithOffer` é o
     * único ponto do `app/` que cria oferta, e ele só o faz junto com um
     * `Product` novo, na mesma transação. Não existe assinatura que aceite um
     * `product_id` existente.
     */
    public function test_o_cadastro_nunca_anexa_oferta_a_item_existente(): void
    {
        $userA = $this->lojista();
        $expositorA = $this->expositor($userA);

        $ofertaA = app(SaveProductWithOffer::class)([
            'item_type' => 'produto',
            'name' => 'Item compartilhavel',
            'slug' => 'item-compartilhavel',
            'price' => 100,
        ], $expositorA);

        $this->assertSame(1, $ofertaA->product->offers()->count());

        // Outro expositor salvando o mesmo nome: nasce um ITEM novo, com slug
        // próprio — nunca uma segunda oferta sobre o item de A.
        $expositorB = $this->expositor();

        $ofertaB = app(SaveProductWithOffer::class)([
            'item_type' => 'produto',
            'name' => 'Item compartilhavel',
            'slug' => 'item-compartilhavel',
            'price' => 200,
        ], $expositorB);

        $this->assertNotSame($ofertaA->product_id, $ofertaB->product_id);
        $this->assertNotSame($ofertaA->product->slug, $ofertaB->product->slug);
        $this->assertSame(1, $ofertaA->product->fresh()->offers()->count());
        $this->assertSame(1, $ofertaB->product->fresh()->offers()->count());
    }

    /** Segunda linha de defesa: o banco recusa duas ofertas do mesmo par. */
    public function test_o_banco_recusa_duas_ofertas_do_mesmo_expositor_no_mesmo_item(): void
    {
        $expositor = $this->expositor();
        $produto = Product::factory()->create(['expositor_id' => $expositor->id]);

        $this->expectException(QueryException::class);

        ProductOffer::factory()->create([
            'product_id' => $produto->id,
            'expositor_id' => $expositor->id,
        ]);
    }

    // ────────────────────────────────────────────────────── I-9 · slug seguro

    public function test_itens_de_mesmo_nome_nao_colidem(): void
    {
        $slugs = collect(range(1, 3))->map(fn () => app(SaveProductWithOffer::class)([
            'item_type' => 'produto',
            'name' => 'Caneca Esmaltada',
            'slug' => 'caneca-esmaltada',
            'price' => 30,
        ], $this->expositor())->product->slug);

        $this->assertSame(3, $slugs->unique()->count(), "Slugs colidiram: {$slugs->implode(', ')}");
        $this->assertFalse(Schema::hasColumn('product_offers', 'slug'), 'A oferta ganhou slug sem decisão.');
    }

    // ─────────────────────────────────────── I-5 · o financeiro não lê o item

    /**
     * Preço e estoque saem da oferta. Depois da 02H isto é impossível de
     * violar por engano — não há coluna em `products` para ler —, e o teste
     * fixa a intenção junto do resto da fronteira.
     */
    public function test_preco_e_estoque_vivem_na_oferta(): void
    {
        $produto = Product::factory()->create(['expositor_id' => $this->expositor()->id]);
        $oferta = $produto->offers()->sole();

        $oferta->update(['price' => 55.50, 'has_stock' => true, 'stock_quantity' => 8, 'reserved_quantity' => 3]);

        $this->assertSame('55.50', $oferta->fresh()->price);
        $this->assertSame(5, $oferta->fresh()->disponivel());

        foreach (['price', 'stock_quantity', 'has_stock'] as $campo) {
            $this->assertArrayNotHasKey($campo, $produto->fresh()->getAttributes());
        }
    }
}
