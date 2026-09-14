<?php

namespace Tests\Feature\CatalogIntelligence;

use App\CatalogIntelligence\Actions\AssociateProductKnowledge;
use App\CatalogIntelligence\Actions\CreateOrUpdateKnowledge;
use App\CatalogIntelligence\Actions\GenerateListingSuggestion;
use App\CatalogIntelligence\Actions\MatchProductKnowledge;
use App\CatalogIntelligence\Contracts\CatalogAiProvider;
use App\CatalogIntelligence\DTOs\ListingContext;
use App\CatalogIntelligence\DTOs\ListingSuggestion;
use App\CatalogIntelligence\DTOs\ProductKnowledgeInput;
use App\CatalogIntelligence\Enums\KnowledgeEntryType;
use App\CatalogIntelligence\Enums\KnowledgeSource;
use App\CatalogIntelligence\Enums\ListingOutcomeState;
use App\CatalogIntelligence\Enums\SuggestionSource;
use App\CatalogIntelligence\Providers\FakeCatalogAiProvider;
use App\CatalogIntelligence\Providers\NullCatalogAiProvider;
use App\CatalogIntelligence\Support\ProductTextNormalizer;
use App\CatalogIntelligence\Support\SimilarityScorer;
use App\Enums\ItemType;
use App\Enums\UserRole;
use App\Livewire\Lojista\Produtos\ProdutoForm;
use App\Models\ContentCategory;
use App\Models\Expositor;
use App\Models\Product;
use App\Models\ProductOffer;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use stdClass;
use Tests\TestCase;

/**
 * CAT-09 — o assistente no cadastro real do lojista.
 *
 * O `ProdutoForm` pede a sugestão, mostra, e deixa o lojista aplicar campo a
 * campo. Tudo o que este arquivo prova se resume a três frases:
 *
 * - **sugerir não é salvar**: gerar e aplicar não emitem uma escrita; gravar
 *   continua sendo o `save()`, pela `SaveProductWithOffer`;
 * - **a tela não passa por cima de ninguém**: nem do texto que o lojista
 *   digitou, nem da autoridade canônica, nem do isolamento entre lojas;
 * - **a inteligência pode cair e o cadastro não**: todo desfecho tem mensagem, e
 *   nenhum impede salvar à mão.
 *
 * Sem rede: o provider é o `Null` padrão ou o `Fake`.
 */
class AssistenteNoCadastroTest extends TestCase
{
    use RefreshDatabase;

    /** O teto do assistente inteiro, fixado pela CAT-05G em `CustoDoAssistenteTest`. */
    private const TETO_DO_ASSISTENTE = 6;

    /** Sem item salvo a similaridade nem é chamada — sobra o casamento (CAT-05G). */
    private const TETO_DO_ASSISTENTE_SEM_PRODUTO = 3;

    /** A categoria da tela e o pai, uma consulta cada, pelo `with('parent')`. */
    private const CONSULTAS_DA_CATEGORIA = 2;

    /**
     * A pergunta `updateCanonical`, feita ao gerar: o catálogo de permissões do
     * spatie (2 — em produção vem do cache) e os papéis e permissões diretas do
     * usuário (2).
     */
    private const CONSULTAS_DA_AUTORIDADE = 4;

    private static int $contador = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);

        // O limiar da CAT-06C, fixado para que os cenários não dependam do ambiente.
        config()->set('catalog-intelligence.fallback.minimum_gaps', 3);
    }

    // ─── Cenário ──────────────────────────────────────────────────────────────

    /** @return array{user: User, expositor: Expositor} */
    private function lojista(): array
    {
        self::$contador++;

        $user = User::factory()->create(['role' => UserRole::Lojista, 'is_active' => true]);

        $expositor = Expositor::create([
            'user_id' => $user->id,
            'name' => 'Ateliê '.self::$contador,
            'slug' => 'atelie-assistente-'.self::$contador,
            'is_active' => true,
        ]);

        return compact('user', 'expositor');
    }

    /** Item salvo, só com o nome: resumo, descrição e categoria vazios. */
    private function item(Expositor $expositor, array $produto = []): ProductOffer
    {
        self::$contador++;

        $product = Product::factory()->create(array_merge([
            'expositor_id' => $expositor->id,
            'item_type' => ItemType::Produto->value,
            'name' => 'Tapete de crochê',
            'slug' => 'tapete-de-croche-'.self::$contador,
            'short_description' => null,
            'description' => null,
            'category_id' => null,
        ], $produto));

        return $product->offers()->where('expositor_id', $expositor->id)->firstOrFail();
    }

    private function conceito(string $nome = 'Crochê', ?string $descricaoCurada = 'Técnica de tecer fios com agulha única.'): void
    {
        app(CreateOrUpdateKnowledge::class)(
            KnowledgeEntryType::Technique,
            $nome,
            KnowledgeSource::HumanCurated,
            description: $descricaoCurada,
        );
    }

    private function associar(Product $produto): void
    {
        app(AssociateProductKnowledge::class)(
            $produto,
            app(MatchProductKnowledge::class)(new ProductKnowledgeInput(name: $produto->name)),
        );
    }

    private function comProvider(CatalogAiProvider $provider): void
    {
        $this->app->instance(CatalogAiProvider::class, $provider);
    }

    private function respostaExterna(
        ?string $nome = null,
        ?string $resumo = null,
        ?string $descricao = null,
        array $palavras = [],
    ): ListingSuggestion {
        return new ListingSuggestion(
            suggestedName: $nome,
            shortDescription: $resumo,
            description: $descricao,
            keywords: $palavras,
            source: SuggestionSource::External,
        );
    }

    private function quebrarOMatcher(): void
    {
        $this->app->bind(MatchProductKnowledge::class, fn () => new class(app(ProductTextNormalizer::class), app(SimilarityScorer::class)) extends MatchProductKnowledge
        {
            public function __invoke(ProductKnowledgeInput $input): Collection
            {
                throw new RuntimeException('base de conhecimento fora do ar');
            }
        });
    }

    /**
     * Registra cada chamada ao assistente — contexto, produto e resultado — e
     * devolve o resultado real. É por ele que se vê o que a tela entregou.
     */
    private function espionarOAssistente(): stdClass
    {
        $espiao = new stdClass;
        $espiao->chamadas = [];

        $this->app->extend(GenerateListingSuggestion::class, fn (GenerateListingSuggestion $real) => new class($real, $espiao) extends GenerateListingSuggestion
        {
            public function __construct(
                private readonly GenerateListingSuggestion $real,
                private readonly stdClass $espiao,
            ) {}

            public function comContexto(ListingContext $contexto, ?Product $produto = null): array
            {
                $resultado = $this->real->comContexto($contexto, $produto);
                $this->espiao->chamadas[] = ['contexto' => $contexto, 'produto' => $produto, 'resultado' => $resultado];

                return $resultado;
            }
        });

        return $espiao;
    }

    /** @return array<int, string> O SQL de escrita emitido durante a ação. */
    private function escritasDurante(callable $acao): array
    {
        $escritas = [];
        $observando = true;

        DB::listen(function (QueryExecuted $consulta) use (&$escritas, &$observando): void {
            if ($observando && preg_match('/^\s*(insert|update|delete|replace|truncate)\b/i', $consulta->sql) === 1) {
                $escritas[] = $consulta->sql;
            }
        });

        $acao();
        $observando = false;

        return $escritas;
    }

    /** @var array<int, string> O SQL da última ação medida — vai na mensagem, para a regressão dizer onde está. */
    private array $sqlMedido = [];

    /** @return int Quantas consultas a ação emitiu. */
    private function contando(callable $acao): int
    {
        DB::enableQueryLog();
        DB::flushQueryLog();

        $acao();

        $this->sqlMedido = array_column(DB::getQueryLog(), 'query');
        DB::disableQueryLog();

        return count($this->sqlMedido);
    }

    /** @param  array<string, mixed>  $tela */
    private function formularioNovo(User $user, array $tela): Testable
    {
        $componente = Livewire::actingAs($user)->test(ProdutoForm::class);

        foreach ($tela as $campo => $valor) {
            $componente->set($campo, $valor);
        }

        return $componente;
    }

    /**
     * Os trechos aparecem no HTML renderizado, nesta ordem.
     *
     * Sobre `html()`, e não `assertSeeInOrder()`: aquele cai na resposta JSON do
     * Livewire, onde todo acento vem escapado como sequência Unicode e "crochê"
     * deixa de ser encontrado.
     *
     * @param  array<int, string>  $trechos
     */
    private function assertTrechosEmOrdem(string $html, array $trechos): void
    {
        $posicao = 0;

        foreach ($trechos as $trecho) {
            $achado = mb_strpos($html, $trecho, $posicao);
            $this->assertNotFalse($achado, "«{$trecho}» não aparece depois do trecho anterior");
            $posicao = $achado + mb_strlen($trecho);
        }
    }

    // ─── Gerar ────────────────────────────────────────────────────────────────

    public function test_gera_sugestao_em_item_novo(): void
    {
        $this->conceito();
        ['user' => $user] = $this->lojista();
        $espiao = $this->espionarOAssistente();

        $this->formularioNovo($user, ['name' => 'Tapete de crochê'])
            ->call('gerarSugestao')
            ->assertHasNoErrors()
            ->assertSet('desfecho', ListingOutcomeState::ProviderUnavailable->value)
            ->assertSet('sugestao.short_description', 'Tapete de crochê. Crochê.')
            ->assertSee('Técnica de tecer fios com agulha única.')
            ->assertSee('Crochê')
            ->assertSeeHtml("aplicarSugestao('short_description')")
            ->assertSeeHtml("aplicarSugestao('description')");

        $this->assertCount(1, $espiao->chamadas);
        $this->assertNull($espiao->chamadas[0]['produto'], 'item novo não tem similaridade a pedir');
    }

    public function test_gera_sugestao_em_item_existente_com_o_produto_para_a_similaridade(): void
    {
        $this->conceito();
        ['user' => $user, 'expositor' => $loja] = $this->lojista();
        $offer = $this->item($loja);
        $this->associar($offer->product);

        $vizinho = $this->item($this->lojista()['expositor'], ['name' => 'Toalha de crochê para abajur']);
        $this->associar($vizinho->product);

        $espiao = $this->espionarOAssistente();

        Livewire::actingAs($user)
            ->test(ProdutoForm::class, ['product' => $offer->product])
            ->call('gerarSugestao')
            ->assertHasNoErrors()
            ->assertSet('sugestao.description', 'Tapete de crochê. Técnica de tecer fios com agulha única.');

        $chamada = $espiao->chamadas[0];
        $this->assertTrue($offer->product->is($chamada['produto']), 'na edição o Product entra como segundo argumento');
        $this->assertSame(
            ['Toalha de crochê para abajur'],
            array_column($chamada['resultado'][1]->similarItems, 'name'),
            'e a similaridade roda sobre ele',
        );
    }

    /**
     * O contexto é o que está **na tela**, e não o que está no banco — e é
     * montado campo a campo, com `knownAttributes` vazio (C-1).
     */
    public function test_valores_nao_salvos_da_tela_entram_no_contexto(): void
    {
        ['user' => $user, 'expositor' => $loja] = $this->lojista();
        $offer = $this->item($loja, ['description' => 'Descrição que está no banco.']);

        $raiz = ContentCategory::create(['name' => 'Casa', 'eixo' => 'servico']);
        $folha = ContentCategory::create(['name' => 'Consertos', 'eixo' => 'servico', 'parent_id' => $raiz->id]);

        $espiao = $this->espionarOAssistente();

        Livewire::actingAs($user)
            ->test(ProdutoForm::class, ['product' => $offer->product])
            ->set('item_type', 'servico')
            ->set('name', 'Conserto de tapete de crochê')
            ->set('short_description', 'Resumo digitado agora.')
            ->set('description', 'Descrição digitada agora, ainda não salva.')
            ->set('category_id', $folha->id)
            ->call('gerarSugestao')
            ->assertHasNoErrors();

        $contexto = $espiao->chamadas[0]['contexto'];
        $this->assertSame(ItemType::Servico, $contexto->itemType);
        $this->assertSame('Conserto de tapete de crochê', $contexto->name);
        $this->assertSame('Resumo digitado agora.', $contexto->existingShortDescription);
        $this->assertSame('Descrição digitada agora, ainda não salva.', $contexto->existingDescription);
        $this->assertSame(['Casa', 'Consertos'], $contexto->categoryPath);
        $this->assertSame([], $contexto->knownAttributes, 'o formulário não tem atributo estruturado, e nada é repassado em bloco');

        $salvo = $offer->product->fresh();
        $this->assertSame('Tapete de crochê', $salvo->name);
        $this->assertSame('Descrição que está no banco.', $salvo->description);
        $this->assertNull($salvo->category_id);
    }

    public function test_nome_vazio_nao_gera_sugestao(): void
    {
        ['user' => $user] = $this->lojista();
        $espiao = $this->espionarOAssistente();

        Livewire::actingAs($user)
            ->test(ProdutoForm::class)
            ->call('gerarSugestao')
            ->assertHasErrors(['name' => 'required'])
            ->assertSet('sugestao', null);

        $this->assertSame([], $espiao->chamadas);
    }

    public function test_gerar_nao_faz_nenhuma_escrita(): void
    {
        $this->conceito();
        ['user' => $user, 'expositor' => $loja] = $this->lojista();
        $offer = $this->item($loja);
        $this->associar($offer->product);

        // O caminho mais largo: conhecimento, similaridade e resposta externa aproveitada.
        $this->comProvider(FakeCatalogAiProvider::disponivel());

        $componente = Livewire::actingAs($user)->test(ProdutoForm::class, ['product' => $offer->product]);

        $produto = $offer->product->fresh()->getAttributes();
        $oferta = $offer->fresh()->getAttributes();
        $associacoes = DB::table('catalog_product_knowledge')->get()->toArray();

        $escritas = $this->escritasDurante(fn () => $componente
            ->call('gerarSugestao')
            ->assertSet('desfecho', ListingOutcomeState::ExternalSuggestionUsed->value));

        $this->assertSame([], $escritas, 'gerar sugestão não pode emitir DML de escrita');
        $this->assertSame($produto, $offer->product->fresh()->getAttributes());
        $this->assertSame($oferta, $offer->fresh()->getAttributes());
        $this->assertEquals($associacoes, DB::table('catalog_product_knowledge')->get()->toArray(), 'nem associação de conhecimento');
    }

    // ─── Aplicar, e só depois salvar ──────────────────────────────────────────

    public function test_aplicar_muda_so_o_estado_da_tela(): void
    {
        $this->conceito();
        ['user' => $user, 'expositor' => $loja] = $this->lojista();
        $offer = $this->item($loja);

        $componente = Livewire::actingAs($user)
            ->test(ProdutoForm::class, ['product' => $offer->product])
            ->call('gerarSugestao');

        $resumo = $componente->get('sugestao.short_description');
        $descricao = $componente->get('sugestao.description');
        $this->assertIsString($resumo);
        $this->assertIsString($descricao);

        $escritas = $this->escritasDurante(fn () => $componente
            ->call('aplicarSugestao', 'short_description')
            ->call('aplicarSugestao', 'description'));

        $componente
            ->assertSet('short_description', $resumo)
            ->assertSet('description', $descricao)
            ->assertSee('Aplicado na tela');

        $this->assertSame([], $escritas, 'aplicar não grava');
        $this->assertNull($offer->product->fresh()->short_description);
        $this->assertNull($offer->product->fresh()->description);
    }

    public function test_salvar_depois_de_aplicar_persiste_pelo_fluxo_normal(): void
    {
        $this->conceito();
        ['user' => $user, 'expositor' => $loja] = $this->lojista();
        $offer = $this->item($loja);

        $componente = Livewire::actingAs($user)
            ->test(ProdutoForm::class, ['product' => $offer->product])
            ->call('gerarSugestao')
            ->call('aplicarSugestao', 'short_description')
            ->call('aplicarSugestao', 'description');

        $resumo = $componente->get('sugestao.short_description');
        $descricao = $componente->get('sugestao.description');

        $componente->call('save')->assertHasNoErrors()->assertSee('atualizado com sucesso');

        $salvo = $offer->product->fresh();
        $this->assertSame($resumo, $salvo->short_description, 'o texto gravado é o exibido');
        $this->assertSame($descricao, $salvo->description);
        $this->assertSame($offer->id, $salvo->offers()->sole()->id, 'a mesma oferta, pelo mesmo caminho');
    }

    public function test_item_novo_aplicado_e_salvo_nasce_com_o_texto_exibido(): void
    {
        $this->conceito();
        ['user' => $user, 'expositor' => $loja] = $this->lojista();

        $componente = $this->formularioNovo($user, ['name' => 'Tapete de crochê', 'price' => '80'])
            ->call('gerarSugestao')
            ->call('aplicarSugestao', 'description');

        $descricao = $componente->get('sugestao.description');

        $this->assertSame(0, Product::count(), 'nada existe antes de salvar');

        $componente->call('save')->assertHasNoErrors()->assertRedirect();

        $produto = Product::sole();
        $this->assertSame($descricao, $produto->description);
        $this->assertNull($produto->short_description, 'o resumo não foi aplicado, e não entrou');
        $this->assertSame($loja->id, $produto->offers()->sole()->expositor_id);
        $this->assertTrue($produto->temDelegacaoCanonicaAtiva(), 'nasceu pela SaveProductWithOffer, com a delegação de sempre');
    }

    public function test_gerar_nao_toca_nos_campos_e_aplicar_nao_sobrescreve_texto_digitado(): void
    {
        $this->conceito();
        ['user' => $user] = $this->lojista();

        $componente = $this->formularioNovo($user, ['name' => 'Tapete de crochê'])
            ->call('gerarSugestao')
            ->assertSet('name', 'Tapete de crochê')
            ->assertSet('short_description', '')
            ->assertSet('description', '');

        $this->assertIsString($componente->get('sugestao.description'));

        // A lojista escreve a própria descrição depois de ver a sugestão.
        $componente
            ->set('description', 'Minha descrição, escrita à mão.')
            ->call('aplicarSugestao', 'description')
            ->assertSet('description', 'Minha descrição, escrita à mão.')
            ->assertSee('Este campo já tem texto seu');
    }

    public function test_nome_so_e_trocado_por_acao_explicita_com_o_nome_atual_visivel(): void
    {
        ['user' => $user] = $this->lojista();
        $this->comProvider(FakeCatalogAiProvider::respondendo($this->respostaExterna(nome: 'Tapete redondo artesanal')));

        $componente = $this->formularioNovo($user, ['name' => 'Tapete de crochê'])
            ->call('gerarSugestao')
            ->assertSet('desfecho', ListingOutcomeState::ExternalSuggestionUsed->value)
            ->assertSet('name', 'Tapete de crochê');

        $this->assertTrechosEmOrdem(
            $componente->html(),
            ['Nome sugerido', 'Nome atual:', 'Tapete de crochê', 'Tapete redondo artesanal', 'Usar este nome'],
        );

        $componente
            ->call('aplicarSugestao', 'name')
            ->assertSet('name', 'Tapete redondo artesanal')
            ->assertSet('slug', 'tapete-redondo-artesanal');
    }

    /** O texto aplicado é o que o servidor gerou: o navegador não reescreve a sugestão. */
    public function test_a_sugestao_nao_pode_ser_adulterada_pelo_cliente(): void
    {
        $this->conceito();
        ['user' => $user] = $this->lojista();

        $componente = $this->formularioNovo($user, ['name' => 'Tapete de crochê'])->call('gerarSugestao');

        $this->expectException(CannotUpdateLockedPropertyException::class);

        $componente->set('sugestao.description', 'Texto que o servidor nunca gerou.');
    }

    // ─── Autoridade e isolamento ──────────────────────────────────────────────

    public function test_sem_autoridade_canonica_a_sugestao_aparece_e_a_aplicacao_fica_bloqueada(): void
    {
        $this->conceito();
        ['user' => $user, 'expositor' => $loja] = $this->lojista();
        $offer = $this->item($loja);
        $offer->product->revogarDelegacaoCanonica();

        $componente = Livewire::actingAs($user)
            ->test(ProdutoForm::class, ['product' => $offer->product])
            ->call('gerarSugestao')
            ->assertSet('sugestaoAplicavel', false)
            ->assertSee('Técnica de tecer fios com agulha única.')
            ->assertSee('não tem autoridade para alterar nome, resumo e descrição deste item')
            ->assertDontSeeHtml("aplicarSugestao('short_description')")
            ->assertDontSeeHtml("aplicarSugestao('description')");

        // O botão não é a proteção: chamar o método direto também não aplica.
        $componente
            ->call('aplicarSugestao', 'short_description')
            ->call('aplicarSugestao', 'description')
            ->assertSet('short_description', '')
            ->assertSet('description', '');
    }

    /** A delegação que cai depois de a sugestão aparecer não vira brecha: aplicar reconfere. */
    public function test_autoridade_revogada_depois_de_gerar_bloqueia_a_aplicacao(): void
    {
        $this->conceito();
        ['user' => $user, 'expositor' => $loja] = $this->lojista();
        $offer = $this->item($loja);

        $componente = Livewire::actingAs($user)
            ->test(ProdutoForm::class, ['product' => $offer->product])
            ->call('gerarSugestao')
            ->assertSet('sugestaoAplicavel', true)
            ->assertSeeHtml("aplicarSugestao('description')");

        $offer->product->revogarDelegacaoCanonica();

        $componente
            ->call('aplicarSugestao', 'description')
            ->assertSet('description', '')
            ->assertSet('sugestaoAplicavel', false)
            ->assertSee('não tem autoridade para alterar nome, resumo e descrição deste item')
            ->assertDontSeeHtml("aplicarSugestao('description')");
    }

    public function test_sem_autoridade_o_salvamento_continua_recusado(): void
    {
        $this->conceito();
        ['user' => $user, 'expositor' => $loja] = $this->lojista();
        $offer = $this->item($loja);
        $offer->product->revogarDelegacaoCanonica();

        $componente = Livewire::actingAs($user)
            ->test(ProdutoForm::class, ['product' => $offer->product])
            ->call('gerarSugestao');

        // Mesmo copiando o texto da sugestão à mão, a identidade do item não muda.
        $componente
            ->set('short_description', $componente->get('sugestao.short_description'))
            ->call('save');

        $this->assertNull($offer->product->fresh()->short_description);
    }

    public function test_outro_lojista_recebe_403_ao_gerar_ou_aplicar_sobre_item_alheio(): void
    {
        $this->conceito();
        ['user' => $dono, 'expositor' => $loja] = $this->lojista();
        ['user' => $intruso] = $this->lojista();
        $offer = $this->item($loja);
        $espiao = $this->espionarOAssistente();

        // Montar já é negado desde a SEC-02...
        Livewire::actingAs($intruso)
            ->test(ProdutoForm::class, ['product' => $offer->product])
            ->assertForbidden();

        // ...mas cada método público é um endpoint próprio. Com o componente
        // montado pelo dono e a sessão trocada, gerar é negado antes de o
        // contexto existir.
        $gerar = Livewire::actingAs($dono)->test(ProdutoForm::class, ['product' => $offer->product]);
        $this->actingAs($intruso);
        $gerar->call('gerarSugestao')->assertForbidden();
        $this->assertSame([], $espiao->chamadas, 'o guard roda antes do assistente');

        $aplicar = Livewire::actingAs($dono)
            ->test(ProdutoForm::class, ['product' => $offer->product])
            ->call('gerarSugestao');
        $this->actingAs($intruso);
        $aplicar->call('aplicarSugestao', 'description')->assertForbidden();

        $this->assertNull($offer->product->fresh()->description);
    }

    // ─── A inteligência pode cair; o cadastro, não ────────────────────────────

    /** A versão pela tela de `ResilienciaDoAssistenteTest::test_cadastro_conclui_com_o_assistente_quebrado`. */
    public function test_falha_do_motor_mostra_aviso_e_nao_impede_salvar_manualmente(): void
    {
        $this->conceito();
        $this->quebrarOMatcher();
        ['user' => $user] = $this->lojista();

        $this->formularioNovo($user, ['name' => 'Tapete de crochê', 'slug' => 'tapete-manual', 'price' => '80'])
            ->call('gerarSugestao')
            ->assertSet('desfecho', ListingOutcomeState::InternalIntelligenceFailed->value)
            ->assertSeeHtml('role="alert"')
            ->assertSee('Tentar novamente')
            ->set('description', 'Descrição escrita à mão.')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('products', ['slug' => 'tapete-manual', 'description' => 'Descrição escrita à mão.']);
    }

    public function test_falha_transitoria_permite_gerar_de_novo_com_os_mesmos_dados(): void
    {
        $this->quebrarOMatcher();
        ['user' => $user] = $this->lojista();
        $espiao = $this->espionarOAssistente();

        $this->formularioNovo($user, ['name' => 'Tapete de crochê'])
            ->call('gerarSugestao')
            ->call('gerarSugestao');

        $this->assertCount(2, $espiao->chamadas, 'InternalIntelligenceFailed convida a repetir');
    }

    public function test_desfecho_que_nao_convida_nao_repete_com_os_mesmos_dados(): void
    {
        $this->conceito();
        ['user' => $user] = $this->lojista();
        $espiao = $this->espionarOAssistente();

        $componente = $this->formularioNovo($user, ['name' => 'Tapete de crochê'])
            ->call('gerarSugestao')
            ->assertSet('desfecho', ListingOutcomeState::ProviderUnavailable->value)
            ->assertDontSee('Tentar novamente')
            ->assertSee('Gerar nova sugestão')
            ->call('gerarSugestao')
            ->assertSee('A sugestão abaixo já corresponde ao que está preenchido');

        $this->assertCount(1, $espiao->chamadas, 'os mesmos dados trariam a mesma resposta');

        // Espaço a mais não é dado novo — o contexto normaliza antes de comparar.
        $componente->set('name', '  Tapete de crochê  ')->call('gerarSugestao');
        $this->assertCount(1, $espiao->chamadas);

        $componente->set('name', 'Tapete redondo de crochê')->call('gerarSugestao');
        $this->assertCount(2, $espiao->chamadas, 'dado novo é pedido novo, não repetição');
    }

    public function test_o_padrao_continua_null_e_nenhuma_chamada_externa_acontece(): void
    {
        Http::fake();

        $this->assertInstanceOf(NullCatalogAiProvider::class, app(CatalogAiProvider::class));

        $this->conceito();
        ['user' => $user] = $this->lojista();

        $this->formularioNovo($user, ['name' => 'Tapete de crochê'])
            ->call('gerarSugestao')
            ->call('aplicarSugestao', 'description')
            ->assertSet('desfecho', ListingOutcomeState::ProviderUnavailable->value)
            ->assertSee('Sugestão preparada apenas com a inteligência interna da Feira.')
            ->assertSeeHtml('role="status"')
            ->assertDontSeeHtml('role="alert"');

        Http::assertNothingSent();
    }

    // ─── Os desfechos ─────────────────────────────────────────────────────────

    /**
     * Prepara o banco e os dublês para o desfecho pedido, e devolve a tela.
     *
     * @return array<string, mixed>
     */
    private function cenario(ListingOutcomeState $estado): array
    {
        $tela = ['name' => 'Tapete de crochê'];

        switch ($estado) {
            case ListingOutcomeState::InternalKnowledgeSufficient:
                $this->conceito();
                $tela['short_description'] = 'Resumo da lojista.';
                $tela['description'] = 'Descrição da lojista.';
                $tela['category_id'] = ContentCategory::create(['name' => 'Casa', 'eixo' => 'produto'])->id;
                break;

            case ListingOutcomeState::InternalKnowledgeInsufficient:
                $tela = ['name' => 'Peça sem tema conhecido', 'short_description' => 'Resumo da lojista.', 'description' => 'Descrição da lojista.'];
                break;

            case ListingOutcomeState::InternalIntelligenceFailed:
                $this->conceito();
                $this->quebrarOMatcher();
                break;

            case ListingOutcomeState::ProviderUnavailable:
                $this->conceito();
                break;

            case ListingOutcomeState::ProviderFailed:
                $this->conceito();
                $this->comProvider(FakeCatalogAiProvider::queFalha());
                break;

            case ListingOutcomeState::ProviderResponseInvalid:
                $this->comProvider(FakeCatalogAiProvider::respondendo($this->respostaExterna(resumo: '   ')));
                break;

            case ListingOutcomeState::ExternalSuggestionUsed:
                $this->comProvider(FakeCatalogAiProvider::disponivel());
                break;

            case ListingOutcomeState::ExternalSuggestionNotUsed:
                $this->conceito();
                $tela['description'] = 'Descrição da lojista.';
                $this->comProvider(FakeCatalogAiProvider::respondendo(
                    $this->respostaExterna(resumo: 'Resumo externo.', descricao: 'Descrição externa.', palavras: ['crochê'])
                ));
                break;
        }

        return $tela;
    }

    /** @return array<string, array{0: ListingOutcomeState, 1: string}> */
    public static function desfechos(): array
    {
        return [
            'conhecimento suficiente' => [ListingOutcomeState::InternalKnowledgeSufficient, 'Sugestão preparada com o conhecimento da Feira.'],
            'conhecimento insuficiente' => [ListingOutcomeState::InternalKnowledgeInsufficient, 'A Feira ainda não conhece este item o bastante para escrever por você.'],
            'motor interno falhou' => [ListingOutcomeState::InternalIntelligenceFailed, 'Não foi possível consultar o conhecimento da Feira agora.'],
            'sem provider' => [ListingOutcomeState::ProviderUnavailable, 'Sugestão preparada apenas com a inteligência interna da Feira.'],
            'provider falhou' => [ListingOutcomeState::ProviderFailed, 'O assistente complementar não respondeu agora.'],
            'resposta inválida' => [ListingOutcomeState::ProviderResponseInvalid, 'foi descartada por não seguir as regras da Feira'],
            'externa aproveitada' => [ListingOutcomeState::ExternalSuggestionUsed, 'complementada por um assistente externo'],
            'externa sem contribuição' => [ListingOutcomeState::ExternalSuggestionNotUsed, 'a consulta complementar não trouxe nada novo'],
        ];
    }

    #[DataProvider('desfechos')]
    public function test_cada_desfecho_tem_mensagem_aviso_so_na_falha_e_convite_so_no_transitorio(ListingOutcomeState $estado, string $mensagem): void
    {
        ['user' => $user] = $this->lojista();

        $componente = $this->formularioNovo($user, $this->cenario($estado))
            ->call('gerarSugestao')
            ->assertHasNoErrors()
            ->assertSet('desfecho', $estado->value)
            ->assertSee($mensagem)
            ->assertSeeHtml($estado->ehFalha() ? 'role="alert"' : 'role="status"')
            ->assertDontSeeHtml($estado->ehFalha() ? 'role="status"' : 'role="alert"');

        $estado->convidaARepetir()
            ? $componente->assertSee('Tentar novamente')
            : $componente->assertDontSee('Tentar novamente');
    }

    public function test_os_desfechos_cobertos_sao_todos_os_estados(): void
    {
        $this->assertEqualsCanonicalizing(
            ListingOutcomeState::cases(),
            array_column(self::desfechos(), 0),
        );
    }

    // ─── Texto hostil (S-2) ───────────────────────────────────────────────────

    /** A sugestão ecoa o nome que o lojista digitou: veio do formulário, deu uma volta e voltou. */
    public function test_texto_hostil_do_lojista_volta_escapado(): void
    {
        $this->conceito();
        ['user' => $user] = $this->lojista();
        $hostil = 'Tapete de crochê <script>alert("xss")</script>';

        $this->formularioNovo($user, ['name' => $hostil])
            ->call('gerarSugestao')
            ->assertSet('sugestao.short_description', $hostil.'. Crochê.')
            ->assertDontSeeHtml('<script>alert("xss")</script>')
            ->assertSeeHtml(e($hostil.'. Crochê.'));
    }

    public function test_texto_hostil_vindo_de_fora_volta_escapado(): void
    {
        ['user' => $user] = $this->lojista();
        $this->comProvider(FakeCatalogAiProvider::respondendo($this->respostaExterna(
            nome: '<img src=x onerror=alert(1)> Tapete',
            descricao: '<a href="javascript:alert(2)">clique</a>',
            palavras: ['<b>negrito</b>'],
        )));

        $this->formularioNovo($user, ['name' => 'Tapete <em>atual</em>'])
            ->call('gerarSugestao')
            ->assertSet('desfecho', ListingOutcomeState::ExternalSuggestionUsed->value)
            ->assertDontSeeHtml('<img src=x onerror=alert(1)>')
            ->assertDontSeeHtml('<a href="javascript:alert(2)">')
            ->assertDontSeeHtml('<b>negrito</b>')
            ->assertDontSeeHtml('<em>atual</em>')
            ->assertSeeHtml(e('<img src=x onerror=alert(1)> Tapete'))
            ->assertSeeHtml(e('<b>negrito</b>'))
            ->assertSeeHtml(e('Tapete <em>atual</em>'));
    }

    // ─── Custo ────────────────────────────────────────────────────────────────

    /**
     * O que o botão acrescenta a um round-trip comum do formulário: o assistente
     * sem similaridade e a categoria da tela com o pai. Medido como diferença
     * contra um `$refresh`, que já paga a hidratação e a renderização.
     */
    public function test_gerar_em_item_novo_custa_o_casamento_mais_a_categoria(): void
    {
        $this->conceito();
        ['user' => $user] = $this->lojista();
        $raiz = ContentCategory::create(['name' => 'Casa', 'eixo' => 'produto']);
        $folha = ContentCategory::create(['name' => 'Tapetes', 'eixo' => 'produto', 'parent_id' => $raiz->id]);

        $componente = $this->formularioNovo($user, ['name' => 'Tapete de crochê', 'category_id' => $folha->id]);

        $base = $this->contando(fn () => $componente->call('$refresh'));
        $comSugestao = $this->contando(fn () => $componente->call('gerarSugestao'));

        $this->assertSame(ListingOutcomeState::ProviderUnavailable->value, $componente->get('desfecho'));
        $this->assertLessThanOrEqual(
            self::TETO_DO_ASSISTENTE_SEM_PRODUTO + self::CONSULTAS_DA_CATEGORIA,
            $comSugestao - $base,
            'gerar em item novo acrescentou '.($comSugestao - $base)." consultas (base {$base}, com sugestão {$comSugestao}):\n".implode("\n", $this->sqlMedido),
        );
    }

    public function test_gerar_em_item_existente_cabe_no_teto_do_assistente_mais_categoria_e_autoridade(): void
    {
        $this->conceito();
        ['user' => $user, 'expositor' => $loja] = $this->lojista();
        $raiz = ContentCategory::create(['name' => 'Casa', 'eixo' => 'produto']);
        $folha = ContentCategory::create(['name' => 'Tapetes', 'eixo' => 'produto', 'parent_id' => $raiz->id]);

        $offer = $this->item($loja, ['category_id' => $folha->id]);
        $this->associar($offer->product);

        for ($i = 1; $i <= 6; $i++) {
            $this->associar($this->item($this->lojista()['expositor'], ['name' => "Peça {$i} de crochê"])->product);
        }

        $componente = Livewire::actingAs($user)->test(ProdutoForm::class, ['product' => $offer->product]);

        $base = $this->contando(fn () => $componente->call('$refresh'));
        $comSugestao = $this->contando(fn () => $componente->call('gerarSugestao'));

        $this->assertNotNull($componente->get('sugestao'));
        $this->assertLessThanOrEqual(
            self::TETO_DO_ASSISTENTE + self::CONSULTAS_DA_CATEGORIA + self::CONSULTAS_DA_AUTORIDADE,
            $comSugestao - $base,
            'gerar em item existente acrescentou '.($comSugestao - $base)." consultas (base {$base}, com sugestão {$comSugestao}):\n".implode("\n", $this->sqlMedido),
        );
    }

    /**
     * A autoridade é perguntada ao gerar e ao aplicar — não a cada renderização.
     *
     * Com a sugestão na tela, o round-trip seguinte custa o mesmo que sem ela. O
     * nome é `wire:model.live`: cada tecla é um round-trip. O usuário é recarregado
     * antes de cada medição, como numa requisição real — no mesmo objeto, papéis,
     * permissões e expositor ficariam em cache e a regressão passaria calada.
     */
    public function test_a_sugestao_na_tela_nao_encarece_os_round_trips_seguintes(): void
    {
        $this->conceito();
        ['user' => $user, 'expositor' => $loja] = $this->lojista();
        $offer = $this->item($loja);

        $componente = Livewire::actingAs($user)->test(ProdutoForm::class, ['product' => $offer->product]);

        $this->actingAs(User::findOrFail($user->id));
        $semSugestao = $this->contando(fn () => $componente->call('$refresh'));

        $componente->call('gerarSugestao');
        $this->assertNotNull($componente->get('sugestao'));

        $this->actingAs(User::findOrFail($user->id));
        $comSugestao = $this->contando(fn () => $componente->call('$refresh'));

        $this->assertSame(
            $semSugestao,
            $comSugestao,
            "com a sugestão exibida o round-trip passou de {$semSugestao} para {$comSugestao} consultas:\n".implode("\n", $this->sqlMedido),
        );
    }
}
