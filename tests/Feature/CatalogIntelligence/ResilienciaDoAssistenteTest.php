<?php

namespace Tests\Feature\CatalogIntelligence;

use App\Actions\Catalog\SaveProductWithOffer;
use App\CatalogIntelligence\Actions\CreateOrUpdateKnowledge;
use App\CatalogIntelligence\Actions\GenerateListingSuggestion;
use App\CatalogIntelligence\Actions\MatchProductKnowledge;
use App\CatalogIntelligence\DTOs\ListingContext;
use App\CatalogIntelligence\DTOs\ProductKnowledgeInput;
use App\CatalogIntelligence\Enums\KnowledgeEntryType;
use App\CatalogIntelligence\Enums\KnowledgeSource;
use App\CatalogIntelligence\Queries\FindSimilarProducts;
use App\CatalogIntelligence\Support\ProductTextNormalizer;
use App\CatalogIntelligence\Support\SimilarityScorer;
use App\Enums\ItemType;
use App\Models\Expositor;
use App\Models\Product;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use PDOException;
use RuntimeException;
use Tests\TestCase;
use Throwable;

/**
 * CAT-05F — a inteligência pode cair; o cadastro, não.
 *
 * A regra 3 das invioláveis: *"Falha da inteligência não bloqueia cadastro.
 * Provider fora do ar, sem credencial, resposta inválida, timeout — o cadastro
 * manual continua funcionando integralmente. **Essa propriedade terá teste
 * explícito.**"*
 *
 * Este é o teste explícito. Hoje ele prova a garantia **do lado do assistente**
 * — que ele captura a própria falha e nunca escreve —, porque o acoplamento com
 * o formulário só chega na CAT-09. É de propósito: chegar lá sem a rede pronta
 * seria construir o acoplamento e só então descobrir se ele é seguro.
 */
class ResilienciaDoAssistenteTest extends TestCase
{
    use RefreshDatabase;

    private function conceito(string $nome, ?string $descricaoCurada = null): void
    {
        app(CreateOrUpdateKnowledge::class)(
            KnowledgeEntryType::Technique,
            $nome,
            KnowledgeSource::HumanCurated,
            description: $descricaoCurada,
        );
    }

    /** Substitui o matcher por um que sempre falha. */
    private function quebrarOMatcher(Throwable $falha): void
    {
        $this->app->bind(MatchProductKnowledge::class, fn () => new class(app(ProductTextNormalizer::class), app(SimilarityScorer::class), $falha) extends MatchProductKnowledge
        {
            public function __construct(
                ProductTextNormalizer $normalizer,
                SimilarityScorer $scorer,
                private readonly Throwable $falha,
            ) {
                parent::__construct($normalizer, $scorer);
            }

            public function __invoke(ProductKnowledgeInput $input): Collection
            {
                throw $this->falha;
            }
        });
    }

    /** Substitui a similaridade por uma que sempre falha. */
    private function quebrarASimilaridade(Throwable $falha): void
    {
        $this->app->bind(FindSimilarProducts::class, fn () => new class(app(SimilarityScorer::class), $falha) extends FindSimilarProducts
        {
            public function __construct(
                SimilarityScorer $scorer,
                private readonly Throwable $falha,
            ) {
                parent::__construct($scorer);
            }

            public function __invoke(Product $product, int $limit = 10): Collection
            {
                throw $this->falha;
            }
        });
    }

    private function contexto(string $nome = 'Tapete de crochê'): ListingContext
    {
        return ListingContext::paraItemNovo(ItemType::Produto, $nome);
    }

    // ── Nenhuma exceção sai do assistente ─────────────────────────────────────

    public function test_falha_no_casamento_devolve_sugestao_vazia_em_vez_de_lancar(): void
    {
        $this->conceito('Crochê', 'Técnica de tecer.');
        $this->quebrarOMatcher(new RuntimeException('base de conhecimento fora do ar'));

        $sugestao = app(GenerateListingSuggestion::class)($this->contexto());

        $this->assertFalse($sugestao->temAlgoAPropor());
        $this->assertNotEmpty($sugestao->missingInformation, 'o que falta continua sendo dito');
    }

    /**
     * Degradação **parcial**: o conhecimento sobrevive à queda da similaridade,
     * porque a lista de semelhantes é acessório e a sugestão não é.
     */
    public function test_falha_na_similaridade_preserva_o_conhecimento(): void
    {
        $this->conceito('Crochê', 'Técnica de tecer.');
        $this->quebrarASimilaridade(new RuntimeException('similaridade fora do ar'));

        $produto = Product::factory()->create([
            'name' => 'Tapete de crochê',
            'description' => null,
            'short_description' => null,
        ]);

        [$sugestao, $contexto] = app(GenerateListingSuggestion::class)
            ->comContexto(ListingContext::deProduct($produto), $produto);

        $this->assertTrue($sugestao->temAlgoAPropor(), 'a sugestão sobrevive');
        $this->assertNotEmpty($contexto->knowledge);
        $this->assertSame([], $contexto->similarItems, 'só o acessório se perde');
    }

    public function test_as_duas_falhando_juntas_ainda_nao_lancam(): void
    {
        $this->conceito('Crochê', 'Técnica de tecer.');
        $this->quebrarOMatcher(new RuntimeException('casamento fora do ar'));
        $this->quebrarASimilaridade(new RuntimeException('similaridade fora do ar'));

        $produto = Product::factory()->create(['name' => 'Tapete de crochê']);

        $sugestao = app(GenerateListingSuggestion::class)(ListingContext::deProduct($produto), $produto);

        $this->assertFalse($sugestao->temAlgoAPropor());
    }

    public function test_a_falha_nao_escreve_nada_no_banco(): void
    {
        $this->conceito('Crochê', 'Técnica de tecer.');
        $this->quebrarOMatcher(new RuntimeException('fora do ar'));

        $produto = Product::factory()->create(['name' => 'Tapete de crochê']);
        $antes = $produto->fresh()->toArray();

        app(GenerateListingSuggestion::class)(ListingContext::deProduct($produto), $produto);

        $this->assertSame($antes, $produto->fresh()->toArray());
        $this->assertDatabaseCount('catalog_product_knowledge', 0);
    }

    // ── A falha é registrada, não engolida em silêncio ────────────────────────

    public function test_a_degradacao_e_registrada_em_log(): void
    {
        Log::spy();

        $this->quebrarOMatcher(new RuntimeException('base fora do ar'));

        app(GenerateListingSuggestion::class)($this->contexto());

        Log::shouldHaveReceived('warning')
            ->withArgs(fn (string $mensagem, array $contexto) => $mensagem === 'catalog-intelligence: assistente degradado'
                && $contexto['etapa'] === 'conhecimento'
                && $contexto['excecao'] === RuntimeException::class)
            ->once();
    }

    public function test_a_etapa_da_falha_e_identificada(): void
    {
        Log::spy();

        $this->conceito('Crochê');
        $this->quebrarASimilaridade(new RuntimeException('similaridade fora do ar'));
        $produto = Product::factory()->create(['name' => 'Tapete de crochê']);

        app(GenerateListingSuggestion::class)(ListingContext::deProduct($produto), $produto);

        Log::shouldHaveReceived('warning')
            ->withArgs(fn (string $m, array $c) => $c['etapa'] === 'semelhantes')
            ->once();
    }

    /**
     * `QueryException::getMessage()` interpola os bindings no SQL. Sem este
     * cuidado, uma falha de banco gravaria em log o texto que o lojista
     * digitou — e a dívida C-2 diz que esse texto pode conter telefone ou
     * e-mail. A §5.3 proíbe conteúdo sensível em log.
     */
    public function test_falha_de_banco_nao_grava_o_texto_do_lojista_em_log(): void
    {
        Log::spy();

        $segredo = 'Tapete artesanal, chama no meu zap 11 90000-0000';

        $this->quebrarOMatcher(new QueryException(
            'sqlite',
            'select * from catalog_knowledge_entries where name = ?',
            [$segredo],
            new PDOException('SQLSTATE[HY000]: general error'),
        ));

        app(GenerateListingSuggestion::class)($this->contexto($segredo));

        Log::shouldHaveReceived('warning')
            ->withArgs(function (string $mensagem, array $contexto) use ($segredo) {
                return ! str_contains($contexto['mensagem'], $segredo)
                    && ! str_contains($contexto['mensagem'], '90000-0000')
                    && ! str_contains($contexto['mensagem'], 'select * from');
            })
            ->once();
    }

    public function test_excecao_comum_mantem_a_mensagem_no_log(): void
    {
        Log::spy();

        $this->quebrarOMatcher(new RuntimeException('base de conhecimento fora do ar'));

        app(GenerateListingSuggestion::class)($this->contexto());

        Log::shouldHaveReceived('warning')
            ->withArgs(fn (string $m, array $c) => $c['mensagem'] === 'base de conhecimento fora do ar')
            ->once();
    }

    // ── A garantia que a CAT-09 vai depender ──────────────────────────────────

    /**
     * **O teste mais importante da subfase.**
     *
     * O cadastro conclui integralmente enquanto o assistente está quebrado. Hoje
     * os dois nem se conhecem — `SaveProductWithOffer` não tem uma linha sobre
     * `CatalogIntelligence` —, então o teste prova a garantia pelo lado do
     * assistente. Ele passa a ter dentes no dia em que a CAT-09 acoplar os
     * dois: se alguém puser a sugestão no caminho do salvamento sem proteção,
     * é aqui que quebra.
     */
    public function test_cadastro_conclui_com_o_assistente_quebrado(): void
    {
        $this->quebrarOMatcher(new RuntimeException('inteligência fora do ar'));
        $this->quebrarASimilaridade(new RuntimeException('inteligência fora do ar'));

        $expositor = Expositor::factory()->create();

        $dados = [
            'item_type' => ItemType::Produto->value,
            'name' => 'Tapete de crochê cadastrado com a inteligência fora do ar',
            'slug' => 'tapete-crochê-sem-inteligencia',
            'short_description' => null,
            'description' => 'Peça artesanal.',
            'category_id' => null,
            'is_digital' => false,
            'price' => 120,
            'is_active' => true,
        ];

        // O assistente é chamado antes e depois do salvamento, como a CAT-09
        // faria — e não impede nem uma coisa nem outra.
        $antes = app(GenerateListingSuggestion::class)(
            ListingContext::paraItemNovo(ItemType::Produto, $dados['name'], description: $dados['description'])
        );
        $this->assertFalse($antes->temAlgoAPropor());

        $offer = app(SaveProductWithOffer::class)($dados, $expositor);

        $this->assertDatabaseHas('products', ['name' => $dados['name']]);
        $this->assertDatabaseHas('product_offers', [
            'id' => $offer->id,
            'expositor_id' => $expositor->id,
        ]);
        $this->assertSame('Peça artesanal.', $offer->product->description);

        $depois = app(GenerateListingSuggestion::class)(
            ListingContext::deProduct($offer->product),
            $offer->product,
        );
        $this->assertFalse($depois->temAlgoAPropor());
    }

    /**
     * A fronteira estrutural que sustenta tudo acima: o caminho de cadastro
     * **não conhece** o módulo de inteligência. Se um dia conhecer, que seja
     * por decisão, e este teste é o que obriga a decisão a ser consciente.
     */
    public function test_o_caminho_de_cadastro_nao_referencia_a_inteligencia(): void
    {
        $arquivos = [
            app_path('Actions/Catalog/SaveProductWithOffer.php'),
            app_path('Livewire/Lojista/Produtos/ProdutoForm.php'),
            app_path('Http/Controllers/Api/V1/Lojista/ProdutoController.php'),
        ];

        foreach ($arquivos as $arquivo) {
            $this->assertStringNotContainsString(
                'CatalogIntelligence',
                file_get_contents($arquivo),
                basename($arquivo).' passou a depender da inteligência — se foi de propósito (CAT-09), revise este teste junto',
            );
        }
    }
}
