<?php

namespace Tests\Feature;

use App\Actions\Catalog\DeleteProductOffer;
use App\Enums\AvaEnrollmentStatus;
use App\Enums\ItemType;
use App\Enums\Modality;
use App\Enums\UserRole;
use App\Livewire\Lojista\Dashboard;
use App\Models\Ava\AvaCourse;
use App\Models\Ava\AvaEnrollment;
use App\Models\Expositor;
use App\Models\Product;
use App\Models\ProductOffer;
use App\Models\ProductOfferFaq;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * CAT-DOM-02A — as superficies leem e escrevem a fonte certa.
 *
 * A CAT-DOM-01 moveu a autoridade comercial para `ProductOffer` e deixou as
 * colunas equivalentes de `products` como espelho, sem leitores. A auditoria da
 * CAT-DOM-02 encontrou quatro pontos onde isso nao era verdade — e nenhum deles
 * depende de multi-oferta para dar errado. Esta classe existe para que nenhum
 * deles volte.
 *
 * O padrao dos testes de espelho e sempre o mesmo: **criar divergencia de
 * proposito** entre a coluna legada e a oferta, e exigir que a tela mostre a
 * oferta. Sem a divergencia o teste passaria com o bug presente, porque hoje o
 * espelho e mantido consistente pela `SaveProductWithOffer` — e um teste que
 * passa com o defeito no lugar nao protege nada.
 *
 * O que esta fase deliberadamente NAO faz: nao habilita multi-oferta, nao move
 * coluna nenhuma e nao decide de quem e o conteudo autoral. As dividas M-01 a
 * M-10 seguem abertas.
 */
class IntegridadeDoCatalogoTest extends TestCase
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

    /**
     * Um item destacado na home, com a oferta de quem o vende.
     *
     * `is_featured` vive na oferta desde a CAT-DOM-01: destaque e decisao da
     * vitrine de quem vende, e `Product::featured()` so enxerga o item por ela.
     */
    private function makeDestaque(Expositor $expositor, ItemType $eixo): ProductOffer
    {
        self::$contador++;

        $product = Product::factory()->create([
            'expositor_id' => $expositor->id,
            'item_type' => $eixo->value,
            'name' => 'Item destacado '.self::$contador,
            'slug' => 'item-destacado-'.self::$contador,
            'is_active' => true,
        ]);

        $offer = $product->offers()->where('expositor_id', $expositor->id)->firstOrFail();
        $offer->update(['is_featured' => true, 'is_active' => true]);

        return $offer->refresh();
    }

    /**
     * Envelhece o espelho legado sem passar por model nem por action.
     *
     * `DB::table` de proposito: a `SaveProductWithOffer` mantem os dois lados
     * iguais, entao pedir a divergencia pelo caminho normal da aplicacao seria
     * impossivel. E exatamente essa a situacao que o espelho nao cobre e que a
     * CAT-DOM-01 §29.3 documentou — escrita direta na oferta nao propaga —,
     * agora reproduzida ao contrario para provar quem manda na leitura.
     *
     * @param  array<string, mixed>  $valores
     */
    private function envelhecerEspelho(ProductOffer $offer, array $valores): void
    {
        DB::table('products')->where('id', $offer->product_id)->update($valores);
    }

    // ─── 02A-1 — a home le a oferta, nunca o espelho ────────────────────────

    public function test_home_mostra_a_modalidade_da_oferta_e_nao_a_do_espelho_legado(): void
    {
        ['expositor' => $expositor] = $this->makeLojista();

        foreach ([ItemType::Servico, ItemType::Cuidado] as $eixo) {
            $offer = $this->makeDestaque($expositor, $eixo);
            $offer->update(['modality' => Modality::Online]);

            // O espelho fica dizendo outra coisa. Se a home o estivesse lendo,
            // ela imprimiria "Presencial e Online".
            $this->envelhecerEspelho($offer, ['modality' => Modality::Ambos->value]);
        }

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee(Modality::Online->label());
        $response->assertDontSee(Modality::Ambos->label());
    }

    public function test_home_mostra_a_duracao_da_oferta_e_nao_a_do_espelho_legado(): void
    {
        ['expositor' => $expositor] = $this->makeLojista();

        foreach ([ItemType::Servico, ItemType::Cuidado] as $eixo) {
            $offer = $this->makeDestaque($expositor, $eixo);

            // A badge de duracao so aparece junto da modalidade, e essa regra
            // de UI nao muda nesta fase: o teste preenche as duas.
            $offer->update(['modality' => Modality::Online, 'duration_min' => 90]);

            $this->envelhecerEspelho($offer, [
                'modality' => Modality::Online->value,
                'duration_min' => 45,
            ]);
        }

        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('1h30min');
        $response->assertDontSee('45min');
    }

    public function test_home_omite_a_badge_quando_a_oferta_nao_tem_modalidade(): void
    {
        ['expositor' => $expositor] = $this->makeLojista();

        $offer = $this->makeDestaque($expositor, ItemType::Servico);
        $offer->update(['modality' => null, 'duration_min' => null]);

        // Ausencia na oferta e ausencia na tela. Cair no espelho para "ter algo
        // a mostrar" seria reintroduzir o leitor legado por outro caminho.
        $this->envelhecerEspelho($offer, [
            'modality' => Modality::Presencial->value,
            'duration_min' => 90,
        ]);

        $response = $this->get('/');

        $response->assertOk();
        $response->assertDontSee(Modality::Presencial->label());
        $response->assertDontSee('1h30min');
    }

    // ─── 02A-2 — FAQ nao desaparece por omissao ─────────────────────────────

    /** @return array{user: User, expositor: Expositor, product: Product} */
    private function makeItemComFaq(): array
    {
        ['user' => $user, 'expositor' => $expositor] = $this->makeLojista();

        $product = Product::factory()->create([
            'expositor_id' => $expositor->id,
            'item_type' => ItemType::Produto->value,
            'name' => 'Bolsa de palha',
            'slug' => 'bolsa-de-palha-'.(++self::$contador),
        ]);

        // CAT-DOM-02E: a FAQ que a API escreve e le e a da oferta.
        // `product_faqs` virou FAQ canonica e nao participa mais deste fluxo.
        ProductOfferFaq::create([
            'product_offer_id' => $product->offers()->sole()->id,
            'question' => 'Qual a origem da palha?',
            'answer' => 'Colhida e trançada aqui mesmo.',
            'sort_order' => 0,
        ]);

        return compact('user', 'expositor', 'product');
    }

    /** @return array<string, mixed> */
    private function payloadDeUpdate(array $extra = []): array
    {
        return array_merge([
            'item_type' => 'produto',
            'name' => 'Bolsa de palha',
            'price' => 120.0,
        ], $extra);
    }

    public function test_update_sem_a_chave_faqs_preserva_as_perguntas_existentes(): void
    {
        ['user' => $user, 'product' => $product] = $this->makeItemComFaq();
        Sanctum::actingAs($user);

        // O app muda so o preco. Nao falou de FAQ, e por isso nada acontece
        // com a FAQ — era aqui que o default `[]` apagava tudo.
        $this->putJson("/api/v1/lojista/produtos/{$product->id}", $this->payloadDeUpdate())
            ->assertOk();

        $this->assertDatabaseCount('product_offer_faqs', 1);
        $this->assertDatabaseHas('product_offer_faqs', [
            'product_offer_id' => $product->offers()->sole()->id,
            'question' => 'Qual a origem da palha?',
        ]);
    }

    public function test_update_com_lista_vazia_remove_todas_as_perguntas(): void
    {
        ['user' => $user, 'product' => $product] = $this->makeItemComFaq();
        Sanctum::actingAs($user);

        // Lista vazia e uma frase completa: "nao quero nenhuma".
        $this->putJson("/api/v1/lojista/produtos/{$product->id}", $this->payloadDeUpdate([
            'faqs' => [],
        ]))->assertOk();

        $this->assertDatabaseCount('product_offer_faqs', 0);
    }

    public function test_update_com_nova_lista_substitui_as_perguntas(): void
    {
        ['user' => $user, 'product' => $product] = $this->makeItemComFaq();
        Sanctum::actingAs($user);

        $this->putJson("/api/v1/lojista/produtos/{$product->id}", $this->payloadDeUpdate([
            'faqs' => [
                ['question' => 'Lava na máquina?', 'answer' => 'Só à mão.'],
                ['question' => 'Tem outras cores?', 'answer' => 'Sob encomenda.'],
            ],
        ]))->assertOk();

        $this->assertDatabaseCount('product_offer_faqs', 2);
        $this->assertDatabaseHas('product_offer_faqs', ['question' => 'Lava na máquina?']);
        $this->assertDatabaseMissing('product_offer_faqs', ['question' => 'Qual a origem da palha?']);

        // A FAQ canonica nao e criada por efeito colateral do writer comercial.
        $this->assertDatabaseCount('product_faqs', 0);
    }

    public function test_criar_item_sem_faqs_continua_nascendo_sem_perguntas(): void
    {
        ['user' => $user] = $this->makeLojista();
        Sanctum::actingAs($user);

        // Na criacao a omissao nunca foi ambigua — nao ha o que preservar —, e
        // o comportamento nao muda nesta fase.
        $this->postJson('/api/v1/lojista/produtos', [
            'item_type' => 'produto',
            'name' => 'Cesta de vime',
            'price' => 70.0,
        ])->assertCreated();

        $this->assertDatabaseCount('product_offer_faqs', 0);
        $this->assertDatabaseCount('product_faqs', 0);
    }

    // ─── 02A-3 — o painel conta ofertas, nao proveniencia ───────────────────

    /**
     * Deixa o lojista com dois produtos em `products.expositor_id` e uma unica
     * oferta viva — a divergencia que a relacao legada nao enxerga.
     *
     * Nao ha multi-oferta aqui: e o fluxo real de quem cadastrou dois itens e
     * removeu um da loja. `DeleteProductOffer` apaga a oferta e deixa o produto
     * no catalogo, exatamente como a CAT-DOM-01 decidiu.
     */
    private function lojistaComUmaOfertaRemovida(): array
    {
        ['user' => $user, 'expositor' => $expositor] = $this->makeLojista();

        $mantido = $this->makeDestaque($expositor, ItemType::Produto);
        $removido = $this->makeDestaque($expositor, ItemType::Produto);

        app(DeleteProductOffer::class)($removido);

        $this->assertSame(2, Product::where('expositor_id', $expositor->id)->count());
        $this->assertSame(1, $expositor->offers()->count());

        return compact('user', 'expositor', 'mantido');
    }

    public function test_painel_web_conta_as_ofertas_da_loja(): void
    {
        ['user' => $user] = $this->lojistaComUmaOfertaRemovida();

        Livewire::actingAs($user)
            ->test(Dashboard::class)
            ->assertViewHas('totalProdutos', 1);
    }

    public function test_painel_da_api_conta_as_ofertas_da_loja(): void
    {
        ['user' => $user] = $this->lojistaComUmaOfertaRemovida();
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/lojista/painel')
            ->assertOk()
            ->assertJsonPath('total_produtos', 1);
    }

    // ─── 02A-4 — o nome publico do expositor ────────────────────────────────

    public function test_meu_aprendizado_mostra_o_nome_do_expositor_autor_do_curso(): void
    {
        ['expositor' => $expositor] = $this->makeLojista();
        $expositor->update(['name' => 'Ateliê da Serra']);

        $product = Product::factory()->create([
            'expositor_id' => $expositor->id,
            'item_type' => ItemType::Servico->value,
            'name' => 'Curso de cestaria',
            'slug' => 'curso-de-cestaria',
            'is_digital' => true,
        ]);

        $course = AvaCourse::create([
            'product_id' => $product->id,
            'published_at' => now()->subMinute(),
        ]);

        $aluno = User::factory()->create();

        AvaEnrollment::create([
            'user_id' => $aluno->id,
            'course_id' => $course->id,
            'status' => AvaEnrollmentStatus::Active,
            'enrolled_at' => now(),
        ]);

        // A tela lia `nome_fantasia`, propriedade que `expositores` nunca teve:
        // o autor do curso saia em branco. A tabela tem uma unica coluna de
        // nome publico, e e ela que todas as outras telas ja usavam.
        $this->actingAs($aluno)
            ->get(route('cliente.ava.index'))
            ->assertOk()
            ->assertSee('Ateliê da Serra');
    }
}
