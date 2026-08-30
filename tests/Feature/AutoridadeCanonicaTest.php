<?php

namespace Tests\Feature;

use App\Enums\ItemType;
use App\Enums\UserRole;
use App\Livewire\Lojista\Produtos\ProdutoForm;
use App\Livewire\Lojista\Produtos\ProdutoIndex;
use App\Models\Expositor;
use App\Models\Product;
use App\Models\ProductOffer;
use App\Models\User;
use Database\Seeders\Concerns\SincronizaOfertaDoItem;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * CAT-DOM-02C — quem pode reescrever a identidade de um item de catálogo.
 *
 * A CAT-DOM-02B congelou a D-CAT-09 e, com ela, um invariante que só um teste
 * consegue proteger de futuras simplificações:
 *
 *     autoridade canônica ≠ quantidade de ofertas
 *
 * É tentador implementar "o lojista edita enquanto for o único ofertante" —
 * é uma linha, passa em qualquer cenário 1:1 e devolve autoridade em silêncio
 * no dia em que o segundo vendedor sair. Os testes desta classe existem para
 * que essa linha não sobreviva a um `composer test`.
 *
 * A segunda metade cobre a D-CAT-10: `products.is_active` é validade canônica e
 * pertence à curadoria; `product_offers.is_active` é disponibilidade comercial
 * e continua sendo do lojista.
 *
 * A terceira prova que o espelho legado morreu — as **doze** colunas comerciais
 * continuam em `products`, e ninguém mais as escreve. `products.is_active` não
 * é uma delas: é validade canônica, e permanece como coluna legítima.
 */
class AutoridadeCanonicaTest extends TestCase
{
    use RefreshDatabase;

    private static int $contador = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    // ─── helpers ────────────────────────────────────────────────────────────

    /** @return array{user: User, expositor: Expositor} */
    private function makeLojista(): array
    {
        self::$contador++;

        $user = User::factory()->create([
            'role' => UserRole::Lojista,
            'is_active' => true,
        ]);

        $expositor = Expositor::create([
            'user_id' => $user->id,
            'name' => 'Ateliê '.self::$contador,
            'slug' => 'atelie-'.self::$contador,
            'is_active' => true,
        ]);

        return compact('user', 'expositor');
    }

    private function makeCurador(): User
    {
        $user = User::factory()->create(['role' => UserRole::Gerente, 'is_active' => true]);
        $user->assignRole(UserRole::Gerente->spatieRole());

        return $user;
    }

    private function makeItem(Expositor $expositor, array $produto = []): ProductOffer
    {
        self::$contador++;

        $product = Product::factory()->create(array_merge([
            'expositor_id' => $expositor->id,
            'item_type' => ItemType::Produto->value,
            'name' => 'Item '.self::$contador,
            'slug' => 'item-'.self::$contador,
        ], $produto));

        return $product->offers()->where('expositor_id', $expositor->id)->firstOrFail();
    }

    /** Uma segunda oferta sobre o mesmo produto, construída direto no banco. */
    private function segundaOferta(Product $product, Expositor $expositor, float $preco = 80): ProductOffer
    {
        // A aplicação não expõe este caminho e continua não expondo — é prova
        // estrutural de isolamento, não funcionalidade pública.
        return ProductOffer::create([
            'product_id' => $product->id,
            'expositor_id' => $expositor->id,
            'price' => $preco,
            'is_active' => true,
        ]);
    }

    // ─── A delegação, e o que ela não é ─────────────────────────────────────

    public function test_delegado_edita_campo_canonico_do_proprio_item(): void
    {
        ['user' => $user, 'expositor' => $expositor] = $this->makeLojista();
        $offer = $this->makeItem($expositor);

        $this->assertTrue($offer->product->temDelegacaoCanonicaAtiva());

        Livewire::actingAs($user)
            ->test(ProdutoForm::class, ['product' => $offer->product])
            ->set('name', 'Nome corrigido pelo delegado')
            ->call('save');

        $this->assertSame('Nome corrigido pelo delegado', $offer->product->fresh()->name);
    }

    public function test_sem_delegacao_o_campo_canonico_e_recusado(): void
    {
        ['user' => $user, 'expositor' => $expositor] = $this->makeLojista();
        $offer = $this->makeItem($expositor);

        $offer->product->revogarDelegacaoCanonica();
        $nome = $offer->product->name;

        Livewire::actingAs($user)
            ->test(ProdutoForm::class, ['product' => $offer->product])
            ->set('name', 'Nome sem autoridade')
            ->call('save');

        $this->assertSame($nome, $offer->product->fresh()->name);
    }

    public function test_ter_oferta_sobre_o_produto_nao_concede_autoridade_canonica(): void
    {
        ['expositor' => $lojaA] = $this->makeLojista();
        ['user' => $userB, 'expositor' => $lojaB] = $this->makeLojista();

        $ofertaA = $this->makeItem($lojaA);
        $this->segundaOferta($ofertaA->product, $lojaB);

        $nome = $ofertaA->product->name;

        // B tem oferta sobre o item e continua sem poder dizer o que ele é.
        Livewire::actingAs($userB)
            ->test(ProdutoForm::class, ['product' => $ofertaA->product])
            ->set('name', 'Nome alterado por B')
            ->call('save');

        $this->assertSame($nome, $ofertaA->product->fresh()->name);
    }

    /**
     * O teste que protege a D-CAT-09 contra a simplificação por contagem.
     *
     * Um produto que já foi compartilhado e voltou a ter uma oferta só não
     * devolve autoridade a ninguém. Se alguém trocar a `ProductPolicy` por
     * `$product->offers()->count() === 1`, é este teste que quebra.
     */
    public function test_voltar_de_duas_ofertas_para_uma_nao_restaura_a_delegacao(): void
    {
        ['user' => $userA, 'expositor' => $lojaA] = $this->makeLojista();
        ['expositor' => $lojaB] = $this->makeLojista();

        $ofertaA = $this->makeItem($lojaA);
        $product = $ofertaA->product;

        // A curadoria compartilhou o item: a delegação de A termina.
        $ofertaB = $this->segundaOferta($product, $lojaB);
        $product->revogarDelegacaoCanonica();

        // B sai, e sobra a oferta de A — exatamente uma.
        $ofertaB->delete();

        $this->assertSame(1, $product->fresh()->offers()->count());
        $this->assertFalse($product->fresh()->temDelegacaoCanonicaAtiva());

        $nome = $product->name;

        Livewire::actingAs($userA)
            ->test(ProdutoForm::class, ['product' => $product])
            ->set('name', 'A acha que voltou a mandar')
            ->call('save');

        $this->assertSame($nome, $product->fresh()->name);
    }

    public function test_expositor_id_sozinho_nao_concede_autoridade(): void
    {
        ['user' => $user, 'expositor' => $expositor] = $this->makeLojista();
        $offer = $this->makeItem($expositor);
        $product = $offer->product;

        // A proveniência continua apontando para o lojista — é registro
        // histórico (D-CAT-11) — e a delegação foi revogada. Se alguma
        // autorização voltar a ler `expositor_id`, este teste quebra.
        $product->revogarDelegacaoCanonica();

        $this->assertSame($expositor->id, $product->fresh()->expositor_id);

        $nome = $product->name;

        Livewire::actingAs($user)
            ->test(ProdutoForm::class, ['product' => $product])
            ->set('name', 'Nome pela proveniencia')
            ->call('save');

        $this->assertSame($nome, $product->fresh()->name);
    }

    public function test_curadoria_edita_campo_canonico(): void
    {
        ['expositor' => $expositor] = $this->makeLojista();
        $offer = $this->makeItem($expositor);
        $offer->product->revogarDelegacaoCanonica();

        $curador = $this->makeCurador();

        $this->assertTrue($curador->can('updateCanonical', $offer->product));
        $this->assertTrue($curador->can('updateStatus', $offer->product));
    }

    public function test_delegado_sem_autoridade_ainda_altera_a_propria_oferta(): void
    {
        ['user' => $user, 'expositor' => $expositor] = $this->makeLojista();
        $offer = $this->makeItem($expositor);
        $offer->product->revogarDelegacaoCanonica();

        // Sem delegação canônica, mas a oferta continua sendo dele: mudar só o
        // preço não toca em nada canônico e não pode ser recusado.
        Livewire::actingAs($user)
            ->test(ProdutoForm::class, ['product' => $offer->product])
            ->set('price', '199.90')
            ->call('save');

        $this->assertSame('199.90', $offer->fresh()->price);
    }

    public function test_api_recusa_alteracao_canonica_sem_delegacao(): void
    {
        ['user' => $user, 'expositor' => $expositor] = $this->makeLojista();
        $offer = $this->makeItem($expositor);
        $offer->product->revogarDelegacaoCanonica();

        Sanctum::actingAs($user);

        // A regra é a mesma nos dois canais: o que o painel recusa, a API
        // recusa. Divergir aqui reabriria a porta pelo lado do app.
        $this->putJson("/api/v1/lojista/produtos/{$offer->product_id}", [
            'item_type' => 'produto',
            'name' => 'Nome alterado pela API',
            'price' => 50,
        ])->assertStatus(403);

        $this->assertNotSame('Nome alterado pela API', $offer->product->fresh()->name);
    }

    public function test_cadastro_novo_nasce_com_delegacao_explicita(): void
    {
        ['user' => $user, 'expositor' => $expositor] = $this->makeLojista();

        Livewire::actingAs($user)
            ->test(ProdutoForm::class)
            ->set('item_type', 'produto')
            ->set('name', 'Cesta nova')
            ->set('slug', 'cesta-nova')
            ->set('price', '40')
            ->call('save');

        $product = Product::where('slug', 'cesta-nova')->firstOrFail();

        $this->assertTrue($product->temDelegacaoCanonicaAtiva());
        $this->assertSame($expositor->id, $product->canonical_delegate_expositor_id);
        $this->assertNotNull($product->canonical_delegated_at);
        $this->assertNull($product->canonical_delegation_revoked_at);
    }

    // ─── D-CAT-10 — validade canônica × disponibilidade comercial ───────────

    public function test_lojista_com_delegacao_nao_altera_a_validade_canonica(): void
    {
        ['user' => $user, 'expositor' => $expositor] = $this->makeLojista();
        $offer = $this->makeItem($expositor);

        $this->assertTrue($offer->product->temDelegacaoCanonicaAtiva());

        // Delegação alcança os campos canônicos e não alcança este.
        $this->assertFalse($user->can('updateStatus', $offer->product));
        $this->assertTrue($user->can('updateCanonical', $offer->product));
    }

    public function test_curadoria_altera_a_validade_canonica(): void
    {
        ['expositor' => $expositor] = $this->makeLojista();
        $offer = $this->makeItem($expositor);

        $curador = $this->makeCurador();
        $this->assertTrue($curador->can('updateStatus', $offer->product));

        $offer->product->forceFill(['is_active' => false])->save();

        $this->assertFalse((bool) $offer->product->fresh()->is_active);
        $this->assertFalse($offer->fresh()->isVigente());
    }

    public function test_seller_continua_dono_do_status_da_propria_oferta(): void
    {
        ['user' => $user, 'expositor' => $expositor] = $this->makeLojista();
        $offer = $this->makeItem($expositor);

        Livewire::actingAs($user)->test(ProdutoIndex::class)->call('toggleActive', $offer->id);

        $this->assertFalse($offer->fresh()->is_active);
        // O item continua válido no catálogo — só não há quem o venda agora.
        $this->assertTrue((bool) $offer->product->fresh()->is_active);
    }

    // ─── Fim do write-through legado ────────────────────────────────────────

    #[DataProvider('camposComerciais')]
    public function test_campo_comercial_e_gravado_so_na_oferta(string $campo, mixed $valor, string $setter): void
    {
        ['user' => $user, 'expositor' => $expositor] = $this->makeLojista();
        $offer = $this->makeItem($expositor);

        $legadoAntes = $offer->product->getAttributes()[$campo];

        Livewire::actingAs($user)
            ->test(ProdutoForm::class, ['product' => $offer->product])
            ->set($setter, $valor)
            ->call('save');

        $depois = $offer->product->fresh()->getAttributes();

        $this->assertNotNull($offer->fresh()->{$campo});
        $this->assertSame(
            $legadoAntes,
            $depois[$campo],
            "A coluna legada products.{$campo} voltou a ser escrita.",
        );
    }

    /** @return array<string, array{0: string, 1: mixed, 2: string}> */
    public static function camposComerciais(): array
    {
        return [
            'price' => ['price', '123.45', 'price'],
            'stock_quantity' => ['stock_quantity', 42, 'stock_quantity'],
            'is_featured' => ['is_featured', true, 'is_featured'],
            'sort_order' => ['sort_order', 7, 'sort_order'],
        ];
    }

    /**
     * Os espelhos comerciais anuláveis, um a um, na criação real.
     *
     * São nove dos doze. Os outros três — `has_stock`, `is_featured` e
     * `sort_order` — são `NOT NULL` com default no schema e não podem ser
     * verificados por `assertNull`; eles são cobertos por
     * `test_campo_comercial_e_gravado_so_na_oferta`, que compara o valor legado
     * antes e depois do salvamento.
     *
     * `is_active` **não** está em nenhuma das duas listas: ele é validade
     * canônica do item (D-CAT-10), não espelho comercial, e continua sendo uma
     * coluna legítima de `products` que a CAT-DOM-02H não remove.
     */
    public function test_criacao_real_nao_copia_os_espelhos_anulaveis(): void
    {
        ['user' => $user] = $this->makeLojista();

        Livewire::actingAs($user)
            ->test(ProdutoForm::class)
            ->set('item_type', 'servico')
            ->set('name', 'Oficina sem espelho')
            ->set('slug', 'oficina-sem-espelho')
            ->set('price', '150.00')
            ->set('price_type', 'por_hora')
            ->set('modality', 'online')
            ->set('duration_min', 90)
            ->set('is_featured', true)
            ->set('sort_order', 3)
            ->call('save');

        $legado = Product::where('slug', 'oficina-sem-espelho')->firstOrFail()->getAttributes();

        foreach (self::ESPELHOS_COMERCIAIS as $campo) {
            $this->assertNull(
                $legado[$campo],
                "products.{$campo} recebeu valor comercial na criação.",
            );
        }
    }

    /** Nove dos doze espelhos — os anuláveis. `is_active` fora, de propósito. */
    private const ESPELHOS_COMERCIAIS = [
        'price', 'price_type', 'modality', 'duration_min',
        'weight', 'height', 'width', 'length',
        'stock_quantity',
    ];

    public function test_desativar_a_oferta_nao_desativa_o_produto(): void
    {
        ['user' => $user, 'expositor' => $expositor] = $this->makeLojista();
        $offer = $this->makeItem($expositor);

        Livewire::actingAs($user)
            ->test(ProdutoForm::class, ['product' => $offer->product])
            ->set('is_active', false)
            ->call('save');

        $this->assertFalse($offer->fresh()->is_active);
        $this->assertTrue((bool) $offer->product->fresh()->is_active);
    }

    public function test_factory_nao_grava_dado_comercial_no_produto(): void
    {
        ['expositor' => $expositor] = $this->makeLojista();

        $product = Product::factory()->create([
            'expositor_id' => $expositor->id,
            'slug' => 'produto-de-fixture',
            'price' => 321.00,
            'stock_quantity' => 9,
        ]);

        $legado = $product->fresh()->getAttributes();
        $offer = $product->offers()->firstOrFail();

        // O açúcar de entrada continua funcionando — e aterrissa na oferta.
        $this->assertSame('321.00', $offer->price);
        $this->assertSame(9, $offer->stock_quantity);

        $this->assertNull($legado['price']);
        $this->assertNull($legado['stock_quantity']);
    }

    public function test_states_de_servico_e_cuidado_mandam_o_comercial_para_a_oferta(): void
    {
        ['expositor' => $expositor] = $this->makeLojista();

        $servico = Product::factory()->servico()->create([
            'expositor_id' => $expositor->id,
            'slug' => 'servico-de-fixture',
        ]);
        $cuidado = Product::factory()->cuidado()->create([
            'expositor_id' => $expositor->id,
            'slug' => 'cuidado-de-fixture',
        ]);

        // `item_type` é do produto...
        $this->assertSame(ItemType::Servico, $servico->fresh()->item_type);
        $this->assertSame(ItemType::Cuidado, $cuidado->fresh()->item_type);

        // ...e a forma de cobrança é da oferta.
        $this->assertSame('fixo', $servico->offers()->first()->price_type?->value);
        $this->assertSame('por_sessao', $cuidado->offers()->first()->price_type?->value);
        $this->assertNull($servico->fresh()->getAttributes()['price_type']);
        $this->assertNull($cuidado->fresh()->getAttributes()['price_type']);
    }

    public function test_seeder_nao_usa_products_como_area_de_passagem_comercial(): void
    {
        ['expositor' => $expositor] = $this->makeLojista();

        $semeador = new class
        {
            use SincronizaOfertaDoItem;

            public function semear(array $chave, array $dados, int $expositorId)
            {
                return $this->semearItemComOferta($chave, $dados, $expositorId);
            }
        };

        $offer = $semeador->semear(['slug' => 'item-semeado'], [
            'expositor_id' => $expositor->id,
            'item_type' => 'produto',
            'name' => 'Item semeado',
            'slug' => 'item-semeado',
            'price' => 77.00,
            'stock_quantity' => 4,
            'is_active' => true,
        ], $expositor->id);

        $this->assertSame('77.00', $offer->price);
        $this->assertSame(4, $offer->stock_quantity);
        $this->assertTrue($offer->product->temDelegacaoCanonicaAtiva());

        $legado = $offer->product->fresh()->getAttributes();
        $this->assertNull($legado['price']);
        $this->assertNull($legado['stock_quantity']);
        // `is_active` é canônico e continua verdadeiro pelo default do schema.
        $this->assertTrue((bool) $legado['is_active']);
    }

    public function test_item_novo_nao_alimenta_as_colunas_comerciais_legadas(): void
    {
        ['user' => $user] = $this->makeLojista();

        Livewire::actingAs($user)
            ->test(ProdutoForm::class)
            ->set('item_type', 'produto')
            ->set('name', 'Bolsa sem espelho')
            ->set('slug', 'bolsa-sem-espelho')
            ->set('price', '99.90')
            ->set('has_stock', true)
            ->set('stock_quantity', 5)
            ->call('save');

        $product = Product::where('slug', 'bolsa-sem-espelho')->firstOrFail();
        $legado = $product->getAttributes();
        $offer = $product->offers()->firstOrFail();

        // A oferta recebeu tudo.
        $this->assertSame('99.90', $offer->price);
        $this->assertSame(5, $offer->stock_quantity);

        // As colunas legadas ficam no default do schema, sem nenhum valor vindo
        // do formulário.
        $this->assertNull($legado['price']);
        $this->assertNull($legado['stock_quantity']);
    }
}
