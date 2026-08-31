<?php

namespace Tests\Feature;

use App\CatalogIntelligence\Enums\KnowledgeEntryType;
use App\CatalogIntelligence\Enums\KnowledgeSource;
use App\CatalogIntelligence\Enums\KnowledgeStatus;
use App\CatalogIntelligence\Queries\FindSimilarProducts;
use App\Enums\UserRole;
use App\Livewire\Lojista\Produtos\ProdutoForm;
use App\Livewire\Lojista\Produtos\ProdutoIndex;
use App\Models\Expositor;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductOffer;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * CAT-DOM-01 — produto mestre × oferta do expositor.
 *
 * Prova o que a decisão de domínio afirma: um item de catálogo não deixa de
 * existir porque quem o vendia saiu da Feira, outro expositor pode oferecê-lo
 * sem assumir a oferta anterior, e o conhecimento acumulado continua servindo à
 * Catalog Intelligence.
 *
 * A segunda metade é a SEC-02 revalidada no novo alvo: agora o que se protege é
 * a oferta, e o produto mestre não pode virar caminho indireto até ela.
 */
class ProdutoMestreOfertaTest extends TestCase
{
    use RefreshDatabase;

    private static int $counter = 0;

    /** @return array{user: User, expositor: Expositor} */
    private function makeLojista(bool $ativo = true): array
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
            'is_active' => $ativo,
        ]);

        return compact('user', 'expositor');
    }

    private function makeItem(Expositor $expositor, array $produto = [], array $oferta = []): ProductOffer
    {
        self::$counter++;

        $product = Product::factory()->create(array_merge([
            'expositor_id' => $expositor->id,
            'name' => 'Tapete de crochê '.self::$counter,
            'slug' => 'tapete-de-croche-'.self::$counter,
        ], $produto));

        $product->ofertaVigente?->update($oferta);

        return $product->offers()->where('expositor_id', $expositor->id)->first();
    }

    // ─── Cenário 1 — a saída do vendedor ────────────────────────────────────

    public function test_produto_sobrevive_a_inativacao_do_expositor(): void
    {
        ['expositor' => $expositor] = $this->makeLojista();
        $offer = $this->makeItem($expositor, ['item_type' => 'produto']);
        $productId = $offer->product_id;

        $this->get('/produtos')->assertOk()->assertSee($offer->product->name);

        $expositor->update(['is_active' => false]);

        // O item continua no catálogo, com a oferta intacta no banco...
        $this->assertDatabaseHas('products', ['id' => $productId]);
        $this->assertDatabaseHas('product_offers', ['id' => $offer->id, 'is_active' => true]);

        // ...mas ninguém o está oferecendo de fato, então ele sai da vitrine.
        $this->get('/produtos')->assertOk()->assertDontSee($offer->product->name);
        $this->assertNull(Product::find($productId)->ofertaVigente);
        $this->assertSame(0, Product::comOfertaVigente()->count());
    }

    public function test_catalogo_e_pagina_da_loja_concordam_sobre_expositor_inativo(): void
    {
        ['expositor' => $expositor] = $this->makeLojista();
        $offer = $this->makeItem($expositor, ['item_type' => 'produto']);

        $expositor->update(['is_active' => false]);

        // O achado que originou a fase: a listagem mostrava o que a página da
        // loja recusava. As duas superfícies agora respondem a mesma coisa.
        $this->get('/produtos')->assertOk()->assertDontSee($offer->product->name);
        $this->get("/loja/{$expositor->slug}")->assertNotFound();
        $this->get("/loja/{$expositor->slug}/{$offer->product->slug}")->assertNotFound();
    }

    public function test_excluir_expositor_nao_apaga_o_produto_do_catalogo(): void
    {
        ['expositor' => $expositor] = $this->makeLojista();
        $offer = $this->makeItem($expositor);
        $productId = $offer->product_id;

        $expositor->delete();

        $this->assertDatabaseMissing('product_offers', ['id' => $offer->id]);
        $this->assertDatabaseHas('products', ['id' => $productId]);
    }

    // ─── Cenário 2 — novo vendedor do mesmo produto ─────────────────────────

    public function test_segundo_expositor_oferece_o_mesmo_produto_sem_assumir_a_oferta_anterior(): void
    {
        ['expositor' => $lojaA] = $this->makeLojista();
        ['expositor' => $lojaB] = $this->makeLojista();

        $ofertaA = $this->makeItem($lojaA, [], ['price' => 50]);
        $produto = $ofertaA->product;

        $ofertaA->update(['is_active' => false]);

        $ofertaB = ProductOffer::create([
            'product_id' => $produto->id,
            'expositor_id' => $lojaB->id,
            'price' => 70,
            'is_active' => true,
        ]);

        // Duas ofertas, um produto: nada foi transferido nem sobrescrito.
        $this->assertSame(2, $produto->offers()->count());
        $this->assertSame($lojaA->id, $ofertaA->fresh()->expositor_id);
        $this->assertSame('50.00', $ofertaA->fresh()->price);
        $this->assertSame($ofertaB->id, $produto->fresh()->ofertaVigente->id);
    }

    public function test_um_expositor_nao_pode_ter_duas_ofertas_do_mesmo_produto(): void
    {
        ['expositor' => $expositor] = $this->makeLojista();
        $offer = $this->makeItem($expositor);

        $this->expectException(UniqueConstraintViolationException::class);

        ProductOffer::create([
            'product_id' => $offer->product_id,
            'expositor_id' => $expositor->id,
            'price' => 10,
        ]);
    }

    // ─── Cenário 3 — semelhantes não são fundidos ───────────────────────────

    public function test_itens_de_mesmo_nome_em_lojas_diferentes_continuam_distintos(): void
    {
        ['expositor' => $lojaA] = $this->makeLojista();
        ['expositor' => $lojaB] = $this->makeLojista();

        $a = $this->makeItem($lojaA, ['name' => 'Tapete de crochê artesanal', 'slug' => 'tapete-a']);
        $b = $this->makeItem($lojaB, ['name' => 'Tapete de crochê artesanal', 'slug' => 'tapete-b']);

        // Em artesanato, mesmo nome não significa mesma peça. A fase criou a
        // capacidade de compartilhar um produto mestre; fundir dois itens é
        // curadoria humana, nunca efeito colateral de cadastro.
        $this->assertNotSame($a->product_id, $b->product_id);
        $this->assertSame(2, Product::count());
    }

    // ─── Catalog Intelligence (01G) ─────────────────────────────────────────

    public function test_conhecimento_sobrevive_a_desativacao_da_oferta(): void
    {
        ['expositor' => $lojaA] = $this->makeLojista();
        ['expositor' => $lojaB] = $this->makeLojista();

        $origem = $this->makeItem($lojaA, ['name' => 'Tapete de crochê', 'slug' => 'tapete-origem']);
        $vizinho = $this->makeItem($lojaB, ['name' => 'Toalha de crochê', 'slug' => 'toalha-vizinha']);

        // `normalized_name` nao e fillable de proposito (CAT-03): o conceito
        // nasce pela Action. Aqui basta a linha, sem exercitar aquela regra.
        $conceitoId = DB::table('catalog_knowledge_entries')->insertGetId([
            'type' => KnowledgeEntryType::Technique->value,
            'name' => 'Croche',
            'normalized_name' => 'croche',
            'status' => KnowledgeStatus::Approved->value,
            'source' => KnowledgeSource::HumanCurated->value,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ([$origem->product_id, $vizinho->product_id] as $productId) {
            DB::table('catalog_product_knowledge')->insert([
                'product_id' => $productId,
                'knowledge_entry_id' => $conceitoId,
                'source' => KnowledgeSource::HumanCurated->value,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $similares = app(FindSimilarProducts::class)($origem->product);
        $this->assertCount(1, $similares);

        // A loja vizinha recolhe a oferta: o item deixa de ser vendável, mas o
        // que ele é — e o que se sabe sobre ele — não muda.
        $vizinho->update(['is_active' => false]);

        $this->assertDatabaseHas('catalog_product_knowledge', [
            'product_id' => $vizinho->product_id,
            'knowledge_entry_id' => $conceitoId,
        ]);
        $this->assertCount(1, app(FindSimilarProducts::class)($origem->product));
    }

    // ─── SEC-02 no novo alvo (01F) ──────────────────────────────────────────

    public function test_lojista_nao_edita_oferta_de_outro_pelo_formulario(): void
    {
        ['expositor' => $dono] = $this->makeLojista();
        ['user' => $invasor] = $this->makeLojista();

        $offer = $this->makeItem($dono, [], ['price' => 49.90]);

        Livewire::actingAs($invasor)
            ->test(ProdutoForm::class, ['product' => $offer->product])
            ->assertForbidden();

        $this->assertSame('49.90', $offer->fresh()->price);
        $this->assertSame($dono->id, $offer->fresh()->expositor_id);
    }

    public function test_lojista_nao_remove_oferta_de_outro(): void
    {
        ['expositor' => $dono] = $this->makeLojista();
        ['user' => $invasor] = $this->makeLojista();

        $offer = $this->makeItem($dono);

        try {
            Livewire::actingAs($invasor)->test(ProdutoIndex::class)->call('delete', $offer->id);
            $this->fail('A remoção de oferta alheia deveria ter sido negada.');
        } catch (ModelNotFoundException) {
            // Esperado: o escopo do painel nem enxerga a oferta de outra loja.
        }

        $this->assertDatabaseHas('product_offers', ['id' => $offer->id]);
    }

    public function test_produto_compartilhado_nao_da_acesso_a_oferta_alheia(): void
    {
        ['expositor' => $lojaA] = $this->makeLojista();
        ['user' => $userB, 'expositor' => $lojaB] = $this->makeLojista();

        $ofertaA = $this->makeItem($lojaA, [], ['price' => 30]);

        $ofertaB = ProductOffer::create([
            'product_id' => $ofertaA->product_id,
            'expositor_id' => $lojaB->id,
            'price' => 80,
            'is_active' => true,
        ]);

        $nomeOriginal = $ofertaA->product->name;

        Livewire::actingAs($userB);

        // B abre o MESMO produto mestre e, mesmo assim, só alcança a oferta
        // dele: o produto compartilhado não é atalho para o preço de A.
        Livewire::test(ProdutoForm::class, ['product' => $ofertaA->product])
            ->assertSet('price', '80.00')
            ->set('price', '85.00')
            ->call('save');

        $this->assertSame('30.00', $ofertaA->fresh()->price);
        $this->assertSame('85.00', $ofertaB->fresh()->price);
        $this->assertSame($lojaA->id, $ofertaA->fresh()->expositor_id);

        // A dívida D-2 fechou aqui, na CAT-DOM-02C: a delegação canônica é de
        // A, que trouxe o item ao catálogo. B tem oferta sobre o produto e
        // mesmo assim não reescreve o que o produto É — nem o nome de A, nem o
        // que as outras lojas exibem.
        Livewire::test(ProdutoForm::class, ['product' => $ofertaA->product])
            ->set('name', 'Nome alterado por B')
            ->call('save');

        $this->assertSame($nomeOriginal, $ofertaA->fresh()->product->name);
    }

    public function test_api_nao_permite_escolher_o_dono_da_oferta(): void
    {
        ['user' => $user, 'expositor' => $expositor] = $this->makeLojista();
        ['expositor' => $alheio] = $this->makeLojista();

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/lojista/produtos', [
            'item_type' => 'produto',
            'name' => 'Bolsa de palha',
            'expositor_id' => $alheio->id,
            'price' => 42,
        ])->assertCreated();

        $this->assertDatabaseHas('product_offers', ['expositor_id' => $expositor->id]);
        $this->assertDatabaseMissing('product_offers', ['expositor_id' => $alheio->id]);
    }

    public function test_api_nao_transfere_a_oferta_em_um_update(): void
    {
        ['user' => $user, 'expositor' => $expositor] = $this->makeLojista();
        ['expositor' => $alheio] = $this->makeLojista();

        $offer = $this->makeItem($expositor);

        Sanctum::actingAs($user);

        $this->putJson("/api/v1/lojista/produtos/{$offer->product_id}", [
            'item_type' => 'produto',
            'name' => 'Nome novo',
            'expositor_id' => $alheio->id,
            'price' => 15,
        ])->assertOk();

        $this->assertSame($expositor->id, $offer->fresh()->expositor_id);
    }

    // ─── Espelho legado D-1 ─────────────────────────────────────────────────

    /**
     * Invertido na CAT-DOM-02C.
     *
     * Este teste nasceu na CAT-DOM-01 exigindo que `products` continuasse
     * espelhando preço e estoque da oferta, para que nenhuma coluna do banco
     * guardasse valor diferente do que era cobrado. A D-CAT-09 encerrou o
     * espelho: com N ofertas por produto uma coluna única não tem o que
     * refletir, e a `ProductOffer` passa a ser a única autoridade de runtime.
     *
     * O que ele prova agora é o oposto — e é o que impede o espelho de voltar.
     */
    public function test_salvar_pela_action_nao_alimenta_mais_o_espelho_legado(): void
    {
        ['user' => $user, 'expositor' => $expositor] = $this->makeLojista();
        $offer = $this->makeItem($expositor, ['item_type' => 'produto'], ['price' => 10]);

        Livewire::actingAs($user)
            ->test(ProdutoForm::class, ['product' => $offer->product])
            ->set('price', '77.50')
            ->set('stock_quantity', 3)
            ->call('save');

        $offer->refresh();
        $legado = $offer->product->fresh()->getAttributes();

        // A oferta é a fonte de verdade, e recebeu a alteração.
        $this->assertSame('77.50', $offer->price);
        $this->assertSame(3, $offer->stock_quantity);

        // As colunas legadas sairam de `products` na CAT-DOM-02H. Ate la o
        // teste comparava o valor legado antes e depois do salvamento; agora a
        // garantia e estrutural — nao ha coluna para um writer reencontrar.
        $this->assertArrayNotHasKey('price', $legado);
        $this->assertArrayNotHasKey('stock_quantity', $legado);
    }

    /**
     * Invertido na CAT-DOM-02C, por D-CAT-10.
     *
     * `products.is_active` é validade canônica e pertence à curadoria;
     * `product_offers.is_active` é disponibilidade comercial e continua sendo
     * do lojista. Alternar a oferta não pode mais alcançar o catálogo.
     */
    public function test_alternar_status_da_oferta_nao_toca_a_validade_canonica(): void
    {
        ['user' => $user, 'expositor' => $expositor] = $this->makeLojista();
        $offer = $this->makeItem($expositor);

        $this->assertTrue((bool) $offer->product->is_active);

        Livewire::actingAs($user)->test(ProdutoIndex::class)->call('toggleActive', $offer->id);

        $this->assertFalse($offer->fresh()->is_active);
        $this->assertTrue((bool) $offer->product->fresh()->is_active);

        Livewire::actingAs($user)->test(ProdutoIndex::class)->call('toggleActive', $offer->id);

        $this->assertTrue($offer->fresh()->is_active);
        $this->assertTrue((bool) $offer->product->fresh()->is_active);
    }

    // ─── Histórico de pedido (01E) ──────────────────────────────────────────

    public function test_remover_a_oferta_nao_apaga_o_item_do_pedido(): void
    {
        ['expositor' => $expositor] = $this->makeLojista();
        $offer = $this->makeItem($expositor, ['item_type' => 'produto'], ['price' => 40]);

        $order = Order::create([
            'reference' => 'PED-'.self::$counter,
            'customer_name' => 'Cliente',
            'customer_whatsapp' => '11999990000',
            'customer_email' => 'cliente@teste.com',
            'address_cep' => '01001000',
            'address_rua' => 'Rua Teste',
            'address_numero' => '10',
            'address_bairro' => 'Centro',
            'address_cidade' => 'Sao Paulo',
            'address_estado' => 'SP',
            'items_total' => 40,
            'shipping_total' => 0,
            'total_amount' => 40,
            'status' => 'aguardando_pagamento',
        ]);

        $item = OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $offer->product_id,
            'product_offer_id' => $offer->id,
            'expositor_id' => $expositor->id,
            'product_name' => $offer->product->name,
            'unit_price' => 40,
            'quantity' => 1,
            'total_price' => 40,
        ]);

        $offer->delete();

        // O pedido continua legível: nome e preço são snapshot do momento da
        // compra, e a FK da oferta apenas perde a referência.
        $item->refresh();
        $this->assertNull($item->product_offer_id);
        $this->assertSame('40.00', $item->unit_price);
        $this->assertSame($expositor->id, $item->expositor_id);
        $this->assertNotEmpty($item->product_name);
        $this->assertDatabaseHas('products', ['id' => $offer->product_id]);
    }

    // ─── Multi-oferta ainda não exposta ─────────────────────────────────────

    public function test_cadastro_sempre_cria_produto_novo_e_nunca_uma_segunda_oferta(): void
    {
        ['user' => $userA, 'expositor' => $lojaA] = $this->makeLojista();
        ['user' => $userB] = $this->makeLojista();

        $ofertaA = $this->makeItem($lojaA, ['name' => 'Bolsa de palha', 'slug' => 'bolsa-de-palha-a']);

        // B cadastra um item de mesmo nome pelo caminho normal do painel.
        Livewire::actingAs($userB)
            ->test(ProdutoForm::class)
            ->set('item_type', 'produto')
            ->set('name', 'Bolsa de palha')
            ->set('slug', 'bolsa-de-palha-b')
            ->set('price', '60')
            ->call('save');

        // Nasce um PRODUTO novo, não uma segunda oferta sobre o de A: a
        // aplicação ainda não expõe multi-oferta, só a suporta no schema.
        $this->assertSame(2, Product::count());
        $this->assertSame(1, $ofertaA->product->offers()->count());
    }

    // ─── Carrinho e pedido (01E) ────────────────────────────────────────────

    public function test_carrinho_guarda_a_oferta_comprada(): void
    {
        ['expositor' => $expositor] = $this->makeLojista();
        $offer = $this->makeItem($expositor, ['item_type' => 'produto'], ['price' => 25]);

        app(CartService::class)->add($offer, 2);

        $this->assertDatabaseHas('cart_items', [
            'product_id' => $offer->product_id,
            'product_offer_id' => $offer->id,
            'expositor_id' => $expositor->id,
            'quantity' => 2,
            'price_snapshot' => 25,
        ]);
    }
}
