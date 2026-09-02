<?php

namespace Tests\Feature\CatalogIntelligence;

use App\CatalogIntelligence\Actions\AssociateProductKnowledge;
use App\CatalogIntelligence\Actions\AttachKnowledgeTerm;
use App\CatalogIntelligence\Actions\CreateOrUpdateKnowledge;
use App\CatalogIntelligence\Actions\GenerateListingSuggestion;
use App\CatalogIntelligence\Actions\MatchProductKnowledge;
use App\CatalogIntelligence\Actions\RelateKnowledge;
use App\CatalogIntelligence\DTOs\KnowledgeCandidate;
use App\CatalogIntelligence\DTOs\ListingContext;
use App\CatalogIntelligence\DTOs\ProductKnowledgeInput;
use App\CatalogIntelligence\Enums\KnowledgeEntryType;
use App\CatalogIntelligence\Enums\KnowledgeRelationType;
use App\CatalogIntelligence\Enums\KnowledgeSource;
use App\CatalogIntelligence\Enums\KnowledgeTermType;
use App\CatalogIntelligence\Models\KnowledgeEntry;
use App\CatalogIntelligence\Support\ContextSanitizer;
use App\Enums\ItemType;
use App\Models\ContentCategory;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * CAT-05G — o custo de consulta do assistente inteiro.
 *
 * A CAT-04 travou as duas metades **em separado**: `MatchProductKnowledge` em
 * ≤3 e `FindSimilarProducts` em ≤3. Faltava a pergunta que o cadastro faz de
 * verdade, que não é por nenhuma das duas isoladamente: *"quanto custa pedir
 * uma sugestão?"*
 *
 * ## O teto é 6, e ele é a soma — não uma folga negociada
 *
 * `GenerateListingSuggestion` chama exatamente uma vez cada metade e não
 * acrescenta consulta própria: compor texto acontece sobre o que já veio em
 * memória. O teto do todo é portanto **3 + 3**, e é essa igualdade que o teste
 * protege. Se ele passasse a 7, a consulta nova estaria ou dentro de um laço,
 * ou numa terceira leitura que ninguém decidiu fazer — e nos dois casos o
 * número é o primeiro a acusar.
 *
 * Fixar 6 em vez de "algo em torno de 6" é deliberado: teto folgado aceita a
 * primeira regressão em silêncio, que é justamente o defeito que um teste de
 * custo existe para pegar.
 *
 * ## O que estes testes medem, e o que fica de fora
 *
 * Medem a **Action**, com o `ListingContext` já montado. Montar o contexto a
 * partir de um `Product` é da CAT-05C e tem custo próprio, medido em
 * `test_montar_o_contexto_custa_uma_consulta_por_ancestral_nao_carregado` — que
 * está aqui exatamente para que a CAT-09 não descubra esse custo em produção.
 *
 * Contagem de consulta, e não tempo: tempo varia com a máquina, uma consulta
 * por item dentro de um laço é defeito em qualquer máquina. É a mesma escolha
 * que a CAT-04 justificou.
 */
class CustoDoAssistenteTest extends TestCase
{
    use RefreshDatabase;

    /** O teto do assistente inteiro: as duas metades da CAT-04, somadas. */
    private const TETO_COMPLETO = 6;

    /** Sem item salvo a similaridade nem é chamada — sobra o casamento. */
    private const TETO_SEM_PRODUTO = 3;

    private function conceito(
        string $nome,
        ?string $descricaoCurada = null,
        KnowledgeEntryType $tipo = KnowledgeEntryType::Technique,
    ): KnowledgeEntry {
        return app(CreateOrUpdateKnowledge::class)(
            $tipo,
            $nome,
            KnowledgeSource::HumanCurated,
            description: $descricaoCurada,
        );
    }

    private function termo(KnowledgeEntry $entry, string $termo, KnowledgeTermType $tipo): void
    {
        app(AttachKnowledgeTerm::class)($entry, $termo, $tipo);
    }

    private function associar(Product $produto): void
    {
        app(AssociateProductKnowledge::class)(
            $produto,
            app(MatchProductKnowledge::class)(new ProductKnowledgeInput(name: $produto->name)),
        );
    }

    /** @return int Quantas consultas a chamada emitiu. */
    private function contando(callable $acao): int
    {
        DB::enableQueryLog();
        DB::flushQueryLog();

        $acao();

        $consultas = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $consultas;
    }

    /**
     * Um cenário que exercita **todos** os caminhos de consulta de uma vez:
     * conceitos com termos, expansão por relação, item salvo e vizinhos
     * associados. Medir num cenário pobre daria um teto que não vale para o
     * caso real.
     */
    private function cenarioCompleto(int $conceitosDeRuido, int $vizinhos): Product
    {
        for ($i = 1; $i <= $conceitosDeRuido; $i++) {
            $ruido = $this->conceito("Tecnica de ruido {$i}");
            $this->termo($ruido, "sinonimo de ruido {$i}", KnowledgeTermType::Synonym);
        }

        $croche = $this->conceito('Crochê', 'Técnica de tecer fios com agulha única.');
        $this->termo($croche, 'crochetar', KnowledgeTermType::Synonym);
        $this->termo($croche, 'peça de linha', KnowledgeTermType::CommercialTerm);

        $artesanato = $this->conceito('Artesanato', 'Trabalho manual.', KnowledgeEntryType::Context);
        app(RelateKnowledge::class)($croche, $artesanato, KnowledgeRelationType::TechniqueOf);

        $origem = Product::factory()->create([
            'name' => 'Tapete de crochê',
            'short_description' => null,
            'description' => null,
        ]);
        $this->associar($origem);

        for ($i = 1; $i <= $vizinhos; $i++) {
            $this->associar(Product::factory()->create(['name' => "Peça {$i} de crochê"]));
        }

        return $origem->fresh();
    }

    // ── O teto do assistente inteiro ──────────────────────────────────────────

    public function test_o_assistente_inteiro_cabe_em_seis_consultas(): void
    {
        $origem = $this->cenarioCompleto(conceitosDeRuido: 5, vizinhos: 6);
        $contexto = ListingContext::deProduct($origem->load('category'));

        $consultas = $this->contando(
            fn () => app(GenerateListingSuggestion::class)($contexto, $origem)
        );

        $this->assertLessThanOrEqual(
            self::TETO_COMPLETO,
            $consultas,
            "o assistente usou {$consultas} consultas; o teto é a soma das duas metades da CAT-04 (3 + 3)",
        );
    }

    /**
     * O teto não é um número que passa por sorte num cenário pequeno: com seis
     * vezes mais conceitos e mais que o triplo de vizinhos, ele é o mesmo.
     */
    public function test_o_custo_do_assistente_nao_cresce_com_o_catalogo(): void
    {
        $origem = $this->cenarioCompleto(conceitosDeRuido: 30, vizinhos: 20);
        $contexto = ListingContext::deProduct($origem->load('category'));

        $consultas = $this->contando(
            fn () => app(GenerateListingSuggestion::class)($contexto, $origem)
        );

        $this->assertLessThanOrEqual(
            self::TETO_COMPLETO,
            $consultas,
            "com 32 conceitos e 21 itens o assistente usou {$consultas} consultas",
        );
    }

    /**
     * O caso do lojista digitando um item novo — o mais frequente na CAT-09, e
     * o mais barato: sem `Product`, `FindSimilarProducts` não é chamada.
     */
    public function test_item_ainda_nao_salvo_custa_so_o_casamento(): void
    {
        $croche = $this->conceito('Crochê', 'Técnica de tecer.');
        $this->termo($croche, 'crochetar', KnowledgeTermType::Synonym);
        $artesanato = $this->conceito('Artesanato', 'Trabalho manual.', KnowledgeEntryType::Context);
        app(RelateKnowledge::class)($croche, $artesanato, KnowledgeRelationType::TechniqueOf);

        $contexto = ListingContext::paraItemNovo(ItemType::Produto, 'Tapete de crochê');

        $consultas = $this->contando(
            fn () => app(GenerateListingSuggestion::class)($contexto)
        );

        $this->assertLessThanOrEqual(
            self::TETO_SEM_PRODUTO,
            $consultas,
            "cadastro em andamento usou {$consultas} consultas; sem item salvo não há similaridade a pagar",
        );
    }

    /**
     * Falhar é barato. A captura da CAT-05F não pode ter virado retentativa: se
     * o motor lança, o assistente não tenta de novo nem procura outro caminho.
     */
    public function test_a_degradacao_nao_custa_mais_que_o_caminho_normal(): void
    {
        $origem = $this->cenarioCompleto(conceitosDeRuido: 3, vizinhos: 3);
        $contexto = ListingContext::deProduct($origem->load('category'));

        DB::statement('DROP TABLE catalog_knowledge_relations');

        $consultas = $this->contando(
            fn () => app(GenerateListingSuggestion::class)($contexto, $origem)
        );

        $this->assertLessThanOrEqual(
            self::TETO_COMPLETO,
            $consultas,
            "com o motor quebrado o assistente usou {$consultas} consultas — degradar não pode custar mais que funcionar",
        );
    }

    // ── A observação de custo herdada da CAT-05E ──────────────────────────────

    /**
     * **A observação nomeada pela CAT-05E, agora medida.**
     *
     * `ContextSanitizer::termosUteis()` lê `$candidato->entry->terms`. A CAT-05E
     * registrou que isso é gratuito hoje porque `MatchProductKnowledge` é o
     * único produtor de `KnowledgeCandidate` e faz `->with('terms')` — e
     * registrou também que um candidato vindo de outro caminho custaria uma
     * consulta por conceito.
     *
     * Este teste prova a **causa**: o eager-load existe e chega até o
     * sanitizer. É a garantia que sustenta o teto de 3 do matcher — e a medição
     * mostrou que a CAT-05E **não** acrescentou custo nenhum, porque o
     * `with('terms')` é da CAT-04 e sempre esteve dentro daquele teto.
     */
    public function test_o_matcher_entrega_candidatos_com_os_termos_ja_carregados(): void
    {
        $croche = $this->conceito('Crochê');
        $this->termo($croche, 'crochetar', KnowledgeTermType::Synonym);
        $trico = $this->conceito('Tricô');
        $this->termo($trico, 'tricotar', KnowledgeTermType::Synonym);

        $candidatos = app(MatchProductKnowledge::class)(
            new ProductKnowledgeInput(name: 'Peça de crochê e tricô')
        );

        $this->assertNotEmpty($candidatos, 'o cenário exige conceitos casados');

        foreach ($candidatos as $candidato) {
            $this->assertTrue(
                $candidato->entry->relationLoaded('terms'),
                "candidato {$candidato->entry->name} veio sem `terms` carregado — o teto de consulta depende disso",
            );
        }
    }

    /**
     * E a **consequência**, para que ela seja um número e não uma suposição.
     *
     * Reduzir conceitos a texto **sem** o eager-load custa uma consulta por
     * conceito. Não é defeito hoje — nenhum caminho produz candidato assim. É a
     * razão pela qual quem criar o segundo produtor de `KnowledgeCandidate`
     * precisa repetir o `->with('terms')`, e é aqui que esse custo fica visível
     * em vez de escrito só num documento.
     */
    public function test_candidato_sem_eager_load_custa_uma_consulta_por_conceito(): void
    {
        foreach (['Crochê', 'Tricô', 'Bordado'] as $nome) {
            $this->termo($this->conceito($nome), 'sinonimo de '.$nome, KnowledgeTermType::Synonym);
        }

        $semEagerLoad = KnowledgeEntry::query()->get()->map(
            fn (KnowledgeEntry $e) => new KnowledgeCandidate($e, 1.0, [])
        );

        $consultas = $this->contando(
            fn () => app(ContextSanitizer::class)->conhecimento($semEagerLoad)
        );

        $this->assertSame(
            3,
            $consultas,
            'sem eager-load o custo é uma consulta por conceito — se isto mudar, a observação de custo da CAT-05E ficou obsoleta',
        );
    }

    /** O mesmo material, com o eager-load que o matcher faz: nenhuma consulta. */
    public function test_com_eager_load_reduzir_conceitos_a_texto_nao_consulta_nada(): void
    {
        foreach (['Crochê', 'Tricô', 'Bordado'] as $nome) {
            $this->termo($this->conceito($nome), 'sinonimo de '.$nome, KnowledgeTermType::Synonym);
        }

        $comEagerLoad = KnowledgeEntry::query()->with('terms')->get()->map(
            fn (KnowledgeEntry $e) => new KnowledgeCandidate($e, 1.0, [])
        );

        $consultas = $this->contando(
            fn () => app(ContextSanitizer::class)->conhecimento($comEagerLoad)
        );

        $this->assertSame(0, $consultas, 'com `terms` carregado a redução é puro PHP');
    }

    // ── O custo de montar o contexto, que é de quem chama ─────────────────────

    /**
     * **Achado da CAT-05G, e é da CAT-09 resolver.**
     *
     * `ListingContext::deProduct()` sobe a árvore de categorias por
     * `$atual->parent`. Com o `Product` cru isso custa uma consulta por
     * ancestral — três níveis, três consultas —, e esse custo **não aparece**
     * no teto do assistente porque é pago antes de a Action ser chamada.
     *
     * Não é defeito da CAT-05C: o DTO recebe um model e não tem como saber o
     * que quem chama carregou. É instrução para quem for chamá-lo de dentro de
     * um formulário — `->with('category.parent')` zera esta conta, e o teste
     * seguinte prova.
     */
    public function test_montar_o_contexto_custa_uma_consulta_por_ancestral_nao_carregado(): void
    {
        $raiz = ContentCategory::create(['name' => 'Artesanato', 'eixo' => 'produto']);
        $meio = ContentCategory::create(['name' => 'Tapeçaria', 'eixo' => 'produto', 'parent_id' => $raiz->id]);
        $folha = ContentCategory::create(['name' => 'Tapetes', 'eixo' => 'produto', 'parent_id' => $meio->id]);

        $id = Product::factory()->create(['name' => 'Tapete de crochê', 'category_id' => $folha->id])->id;
        $cru = Product::query()->findOrFail($id);

        $consultas = $this->contando(fn () => ListingContext::deProduct($cru));

        $this->assertSame(
            3,
            $consultas,
            'três níveis de categoria, três consultas — é o custo que a CAT-09 precisa conhecer',
        );
    }

    public function test_com_a_categoria_e_o_pai_carregados_montar_o_contexto_nao_consulta(): void
    {
        $raiz = ContentCategory::create(['name' => 'Artesanato', 'eixo' => 'produto']);
        $folha = ContentCategory::create(['name' => 'Tapetes', 'eixo' => 'produto', 'parent_id' => $raiz->id]);

        $id = Product::factory()->create(['name' => 'Tapete de crochê', 'category_id' => $folha->id])->id;
        $carregado = Product::query()->with('category.parent')->findOrFail($id);

        $consultas = $this->contando(fn () => ListingContext::deProduct($carregado));

        $this->assertSame(0, $consultas, '`with(category.parent)` paga a árvore junto com o item');
    }
}
