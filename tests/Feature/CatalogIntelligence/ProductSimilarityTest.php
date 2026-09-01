<?php

namespace Tests\Feature\CatalogIntelligence;

use App\CatalogIntelligence\Actions\AssociateProductKnowledge;
use App\CatalogIntelligence\Actions\AttachKnowledgeTerm;
use App\CatalogIntelligence\Actions\CreateOrUpdateKnowledge;
use App\CatalogIntelligence\Actions\MatchProductKnowledge;
use App\CatalogIntelligence\Actions\RelateKnowledge;
use App\CatalogIntelligence\DTOs\ProductKnowledgeInput;
use App\CatalogIntelligence\Enums\KnowledgeEntryType;
use App\CatalogIntelligence\Enums\KnowledgeRelationType;
use App\CatalogIntelligence\Enums\KnowledgeSource;
use App\CatalogIntelligence\Enums\KnowledgeStatus;
use App\CatalogIntelligence\Enums\KnowledgeTermType;
use App\CatalogIntelligence\Enums\MatchType;
use App\CatalogIntelligence\Models\KnowledgeEntry;
use App\CatalogIntelligence\Queries\FindSimilarProducts;
use App\CatalogIntelligence\Support\ProductTextNormalizer;
use App\CatalogIntelligence\Support\SimilarityScorer;
use App\Models\Expositor;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * CAT-04 — motor de similaridade interna.
 *
 * A pergunta que estes testes protegem não é "o score está alto?", e sim
 * "conseguimos explicar por quê?". Por isso quase todo caso verifica a
 * evidência junto com o resultado.
 */
class ProductSimilarityTest extends TestCase
{
    use RefreshDatabase;

    private function conceito(
        KnowledgeEntryType $tipo,
        string $nome,
        array $termos = [],
        KnowledgeStatus $status = KnowledgeStatus::Approved,
    ): KnowledgeEntry {
        $entry = app(CreateOrUpdateKnowledge::class)(
            $tipo,
            $nome,
            $status === KnowledgeStatus::Approved ? KnowledgeSource::HumanCurated : KnowledgeSource::Derived,
        );

        if ($status !== KnowledgeStatus::Approved) {
            $entry->update(['status' => $status]);
            $entry->refresh();
        }

        foreach ($termos as $t) {
            app(AttachKnowledgeTerm::class)($entry, $t, KnowledgeTermType::Synonym);
        }

        return $entry;
    }

    private function match(string $nome, ?string $descricao = null, ?string $categoria = null)
    {
        return app(MatchProductKnowledge::class)(
            new ProductKnowledgeInput(name: $nome, description: $descricao, categoryName: $categoria)
        );
    }

    private function nomesDe($candidatos): array
    {
        return $candidatos->map(fn ($c) => $c->entry->name)->sort()->values()->all();
    }

    // ── Normalização ──────────────────────────────────────────────────────────

    public function test_product_text_uses_the_same_normalization_as_the_knowledge_base(): void
    {
        $n = app(ProductTextNormalizer::class);
        $entry = $this->conceito(KnowledgeEntryType::Technique, 'Crochê');

        foreach (['Crochê', 'croche', 'CROCHÊ', 'croché'] as $variacao) {
            $this->assertTrue(
                $n->contemFrase($n->normalizedHaystack("Tapete de {$variacao} artesanal"), $entry->normalized_name),
                "não casou: {$variacao}"
            );
        }
    }

    public function test_matching_is_by_whole_word_not_substring(): void
    {
        $this->conceito(KnowledgeEntryType::Technique, 'Crochê');

        $this->assertCount(0, $this->match('Curso para crocheteiros iniciantes'));
        $this->assertCount(1, $this->match('Tapete de crochê'));
    }

    public function test_tokens_drop_stopwords(): void
    {
        $tokens = app(ProductTextNormalizer::class)->tokens('Tapete de crochê para a casa');

        $this->assertContains('tapete', $tokens);
        $this->assertContains('croche', $tokens);
        $this->assertNotContains('de', $tokens);
        $this->assertNotContains('para', $tokens);
    }

    // ── Match direto ──────────────────────────────────────────────────────────

    public function test_matches_concept_by_canonical_name(): void
    {
        $this->conceito(KnowledgeEntryType::Technique, 'Crochê');

        $candidatos = $this->match('Tapete artesanal de crochê');

        $this->assertSame(['Crochê'], $this->nomesDe($candidatos));
        $this->assertSame(MatchType::ExactName, $candidatos->first()->melhorTipo());
    }

    public function test_matches_concept_by_term(): void
    {
        $this->conceito(KnowledgeEntryType::Attribute, 'Feito à mão', ['artesanal']);

        $candidatos = $this->match('Bolsa tecida artesanal');

        $this->assertSame(['Feito à mão'], $this->nomesDe($candidatos));
        $this->assertSame(MatchType::ExactTerm, $candidatos->first()->melhorTipo());
    }

    /** Conceito composto tem de ser reconhecido inteiro, não quebrado por espaço. */
    public function test_matches_multi_word_concepts(): void
    {
        $this->conceito(KnowledgeEntryType::Attribute, 'Feito à mão');
        $this->conceito(KnowledgeEntryType::Material, 'Ervas medicinais');
        $this->conceito(KnowledgeEntryType::Theme, 'Economia solidária');

        $candidatos = $this->match('Kit de ervas medicinais, feito à mão, da economia solidária');

        $this->assertSame(['Economia solidária', 'Ervas medicinais', 'Feito à mão'], $this->nomesDe($candidatos));
    }

    /**
     * O caso que a auditoria dos 75 itens revelou: "solidária" aparece em
     * "Consultoria Solidária", que não é economia solidária.
     */
    public function test_partial_phrase_does_not_match(): void
    {
        $this->conceito(KnowledgeEntryType::Theme, 'Economia solidária');

        $this->assertCount(0, $this->match('Consultoria Solidária Demo'));
    }

    public function test_category_name_feeds_the_match(): void
    {
        $this->conceito(KnowledgeEntryType::Theme, 'Artesanato');

        $candidatos = $this->match('Peça única', descricao: 'Sem menção ao tema.', categoria: 'Artesanato');

        $this->assertSame(['Artesanato'], $this->nomesDe($candidatos));
    }

    public function test_empty_text_yields_no_candidates(): void
    {
        $this->conceito(KnowledgeEntryType::Technique, 'Crochê');

        $this->assertCount(0, $this->match('   '));
    }

    // ── Somente conhecimento aprovado ─────────────────────────────────────────

    /** @dataProvider statusNaoUtilizaveis */
    public function test_non_approved_knowledge_never_matches(KnowledgeStatus $status): void
    {
        $this->conceito(KnowledgeEntryType::Technique, 'Crochê', status: $status);

        $this->assertCount(0, $this->match('Tapete artesanal de crochê'), $status->value);
    }

    public static function statusNaoUtilizaveis(): array
    {
        return [
            'draft' => [KnowledgeStatus::Draft],
            'rejected' => [KnowledgeStatus::Rejected],
            'inactive' => [KnowledgeStatus::Inactive],
        ];
    }

    public function test_approved_knowledge_matches(): void
    {
        $this->conceito(KnowledgeEntryType::Technique, 'Crochê', status: KnowledgeStatus::Approved);

        $this->assertCount(1, $this->match('Tapete artesanal de crochê'));
    }

    // ── Relações ──────────────────────────────────────────────────────────────

    public function test_relations_bring_context_with_lower_weight_than_direct(): void
    {
        $croche = $this->conceito(KnowledgeEntryType::Technique, 'Crochê');
        $artesanato = $this->conceito(KnowledgeEntryType::Theme, 'Artesanato');
        app(RelateKnowledge::class)($croche, $artesanato, KnowledgeRelationType::TechniqueOf);

        $candidatos = $this->match('Tapete de crochê');

        $this->assertSame(['Artesanato', 'Crochê'], $this->nomesDe($candidatos));

        $direto = $candidatos->firstWhere(fn ($c) => $c->entry->name === 'Crochê');
        $relacionado = $candidatos->firstWhere(fn ($c) => $c->entry->name === 'Artesanato');

        $this->assertGreaterThan($relacionado->score, $direto->score);
        $this->assertTrue($direto->temEvidenciaDireta());
        $this->assertFalse($relacionado->temEvidenciaDireta());
        $this->assertSame(MatchType::Related, $relacionado->melhorTipo());
    }

    public function test_relation_expansion_stops_at_one_step(): void
    {
        $a = $this->conceito(KnowledgeEntryType::Technique, 'Crochê');
        $b = $this->conceito(KnowledgeEntryType::Theme, 'Artesanato');
        $c = $this->conceito(KnowledgeEntryType::Context, 'Decoração');

        app(RelateKnowledge::class)($a, $b, KnowledgeRelationType::TechniqueOf);
        app(RelateKnowledge::class)($b, $c, KnowledgeRelationType::RelatedTo);

        $this->assertSame(['Artesanato', 'Crochê'], $this->nomesDe($this->match('Tapete de crochê')));
    }

    public function test_relation_to_non_approved_concept_is_ignored(): void
    {
        $croche = $this->conceito(KnowledgeEntryType::Technique, 'Crochê');
        $rascunho = $this->conceito(KnowledgeEntryType::Theme, 'Artesanato', status: KnowledgeStatus::Draft);
        app(RelateKnowledge::class)($croche, $rascunho, KnowledgeRelationType::TechniqueOf);

        $this->assertSame(['Crochê'], $this->nomesDe($this->match('Tapete de crochê')));
    }

    // ── Score ─────────────────────────────────────────────────────────────────

    public function test_weights_come_from_a_single_source(): void
    {
        $scorer = app(SimilarityScorer::class);

        $this->assertSame(SimilarityScorer::PESO_NOME_EXATO, $scorer->pesoDoMatch(MatchType::ExactName));
        $this->assertSame(SimilarityScorer::PESO_TERMO_EXATO, $scorer->pesoDoMatch(MatchType::ExactTerm));
        $this->assertSame(SimilarityScorer::PESO_RELACIONADO, $scorer->pesoDoMatch(MatchType::Related));

        $this->assertGreaterThan(
            $scorer->pesoDoMatch(MatchType::ExactTerm),
            $scorer->pesoDoMatch(MatchType::ExactName)
        );
        $this->assertGreaterThan(
            $scorer->pesoDoMatch(MatchType::Related),
            $scorer->pesoDoMatch(MatchType::ExactTerm)
        );
    }

    public function test_confirmed_concept_outweighs_derived_one(): void
    {
        $scorer = app(SimilarityScorer::class);

        $this->assertGreaterThan(
            $scorer->pesoDoConceitoCompartilhado(KnowledgeSource::HumanCurated, KnowledgeSource::Derived),
            $scorer->pesoDoConceitoCompartilhado(KnowledgeSource::HumanCurated, KnowledgeSource::HumanCurated),
        );
    }

    public function test_candidates_are_ordered_by_score(): void
    {
        $croche = $this->conceito(KnowledgeEntryType::Technique, 'Crochê');
        $artesanato = $this->conceito(KnowledgeEntryType::Theme, 'Artesanato');
        app(RelateKnowledge::class)($croche, $artesanato, KnowledgeRelationType::TechniqueOf);

        $scores = $this->match('Tapete de crochê')->map(fn ($c) => $c->score)->all();

        $this->assertSame($scores, collect($scores)->sortDesc()->values()->all());
    }

    // ── Explicabilidade ───────────────────────────────────────────────────────

    public function test_every_candidate_carries_reasons(): void
    {
        $this->conceito(KnowledgeEntryType::Attribute, 'Feito à mão', ['artesanal']);

        $candidato = $this->match('Bolsa tecida artesanal')->first();

        $this->assertNotEmpty($candidato->reasons);
        $this->assertStringContainsString('artesanal', $candidato->reasons[0]->description);
        $this->assertArrayHasKey('reasons', $candidato->toArray());
    }

    // ── Candidate ≠ association ───────────────────────────────────────────────

    public function test_matching_writes_nothing(): void
    {
        $this->conceito(KnowledgeEntryType::Technique, 'Crochê');
        Product::factory()->create(['name' => 'Tapete de crochê']);

        $this->match('Tapete de crochê');

        $this->assertSame(0, DB::table('catalog_product_knowledge')->count());
    }

    public function test_association_persists_only_direct_evidence(): void
    {
        $croche = $this->conceito(KnowledgeEntryType::Technique, 'Crochê');
        $artesanato = $this->conceito(KnowledgeEntryType::Theme, 'Artesanato');
        app(RelateKnowledge::class)($croche, $artesanato, KnowledgeRelationType::TechniqueOf);

        $product = Product::factory()->create(['name' => 'Tapete de crochê']);
        $resultado = app(AssociateProductKnowledge::class)($product, $this->match('Tapete de crochê'));

        $this->assertSame(1, $resultado['associados']);
        $this->assertSame(1, $resultado['ignorados']);
        $this->assertDatabaseHas('catalog_product_knowledge', [
            'product_id' => $product->id,
            'knowledge_entry_id' => $croche->id,
            'source' => KnowledgeSource::Derived->value,
        ]);
        $this->assertDatabaseMissing('catalog_product_knowledge', [
            'product_id' => $product->id,
            'knowledge_entry_id' => $artesanato->id,
        ]);
    }

    public function test_association_is_idempotent(): void
    {
        $this->conceito(KnowledgeEntryType::Technique, 'Crochê');
        $product = Product::factory()->create(['name' => 'Tapete de crochê']);

        $associar = app(AssociateProductKnowledge::class);
        $associar($product, $this->match('Tapete de crochê'));
        $segunda = $associar($product, $this->match('Tapete de crochê'));

        $this->assertSame(0, $segunda['associados']);
        $this->assertSame(1, $segunda['ja_existentes']);
        $this->assertSame(1, DB::table('catalog_product_knowledge')->count());
    }

    /** Curadoria humana não é rebaixada por uma passagem automática. */
    public function test_human_association_is_not_overwritten(): void
    {
        $croche = $this->conceito(KnowledgeEntryType::Technique, 'Crochê');
        $product = Product::factory()->create(['name' => 'Tapete de crochê']);

        DB::table('catalog_product_knowledge')->insert([
            'product_id' => $product->id,
            'knowledge_entry_id' => $croche->id,
            'source' => KnowledgeSource::HumanCurated->value,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        app(AssociateProductKnowledge::class)($product, $this->match('Tapete de crochê'));

        $this->assertSame(
            KnowledgeSource::HumanCurated->value,
            DB::table('catalog_product_knowledge')->where('product_id', $product->id)->value('source')
        );
    }

    public function test_association_never_creates_or_approves_knowledge(): void
    {
        $this->conceito(KnowledgeEntryType::Technique, 'Crochê', status: KnowledgeStatus::Draft);
        $product = Product::factory()->create(['name' => 'Tapete de crochê']);

        app(AssociateProductKnowledge::class)($product, $this->match('Tapete de crochê'));

        $this->assertSame(1, KnowledgeEntry::count());
        $this->assertSame(KnowledgeStatus::Draft, KnowledgeEntry::first()->status);
        $this->assertSame(0, DB::table('catalog_product_knowledge')->count());
    }

    // ── Similaridade produto → produto ────────────────────────────────────────

    /** @return array{0: Product, 1: Product} */
    private function doisItensComConhecimentoEmComum(): array
    {
        $this->conceito(KnowledgeEntryType::Technique, 'Crochê');
        $this->conceito(KnowledgeEntryType::Attribute, 'Feito à mão', ['artesanal']);
        $this->conceito(KnowledgeEntryType::Context, 'Decoração');

        $a = Product::factory()->create(['name' => 'Tapete de crochê artesanal para decoração']);
        $b = Product::factory()->create(['name' => 'Toalha de crochê artesanal para abajur, decoração']);

        $associar = app(AssociateProductKnowledge::class);
        foreach ([$a, $b] as $p) {
            $associar($p, $this->match($p->name));
        }

        return [$a, $b];
    }

    public function test_products_sharing_concepts_are_similar(): void
    {
        [$a, $b] = $this->doisItensComConhecimentoEmComum();

        $similares = app(FindSimilarProducts::class)($a);

        $this->assertCount(1, $similares);
        $this->assertSame($b->id, $similares->first()->product->id);
        $this->assertGreaterThan(0, $similares->first()->score);
    }

    public function test_similarity_explains_itself(): void
    {
        [$a] = $this->doisItensComConhecimentoEmComum();

        $similar = app(FindSimilarProducts::class)($a)->first();

        $this->assertContains('Crochê', $similar->sharedConcepts);
        $this->assertContains('Feito à mão', $similar->sharedConcepts);

        $frases = array_map(fn ($r) => $r->description, $similar->reasons);
        $this->assertContains('Técnica compartilhada: Crochê.', $frases);
        $this->assertContains('Atributo compartilhado: Feito à mão.', $frases);
    }

    public function test_product_is_never_similar_to_itself(): void
    {
        [$a] = $this->doisItensComConhecimentoEmComum();

        $this->assertFalse(
            app(FindSimilarProducts::class)($a)->contains(fn ($s) => $s->product->id === $a->id)
        );
    }

    public function test_product_without_associations_has_no_similars(): void
    {
        $this->doisItensComConhecimentoEmComum();
        $sozinho = Product::factory()->create(['name' => 'Item sem conceito algum']);

        $this->assertCount(0, app(FindSimilarProducts::class)($sozinho));
    }

    // ── Vigência: só se sugere o que alguém está vendendo (CAT-05B, M-17) ─────

    /**
     * Os três eixos da vigência, um por vez.
     *
     * Antes da CAT-05B só o primeiro era coberto, e só ele funcionava: o filtro
     * era `products.is_active` solto. Os outros dois passavam despercebidos
     * porque `ProductFactory` cria tudo ativo — e no banco de desenvolvimento
     * não havia um único expositor inativo para denunciar o furo.
     *
     * @return array<string, array{0: \Closure}>
     */
    public static function eixosDeVigencia(): array
    {
        return [
            'produto sem validade canônica' => [
                fn (Product $b) => $b->update(['is_active' => false]),
            ],
            'oferta desligada pelo lojista' => [
                fn (Product $b) => $b->offers()->update(['is_active' => false]),
            ],
            'expositor fora da Feira' => [
                fn (Product $b) => $b->offers->first()->expositor->update(['is_active' => false]),
            ],
        ];
    }

    /** @dataProvider eixosDeVigencia */
    public function test_item_sem_oferta_vigente_nao_e_sugerido_como_semelhante(\Closure $tirarDeVigencia): void
    {
        [$a, $b] = $this->doisItensComConhecimentoEmComum();

        // Antes: os dois se enxergam.
        $this->assertCount(1, app(FindSimilarProducts::class)($a));

        $tirarDeVigencia($b->load('offers.expositor'));

        $this->assertCount(0, app(FindSimilarProducts::class)($a));
    }

    /** Sem nenhuma oferta não há quem venda — e sugerir isso é sugerir um 404. */
    public function test_item_sem_oferta_alguma_nao_e_sugerido_como_semelhante(): void
    {
        [$a] = $this->doisItensComConhecimentoEmComum();

        $orfao = Product::factory()->semOferta()->create([
            'name' => 'Manta de crochê artesanal para decoração',
        ]);
        app(AssociateProductKnowledge::class)($orfao, $this->match($orfao->name));

        $encontrados = app(FindSimilarProducts::class)($a)->pluck('product.id')->all();

        $this->assertNotContains($orfao->id, $encontrados);
    }

    /**
     * A contrapartida da D-CAT-21: o item sem oferta **continua no catálogo
     * interno e na Catalog Intelligence**.
     *
     * A vigência filtra quem é *oferecido como referência*, nunca quem pode
     * *pedir* referência. Se filtrasse os dois lados, o produto que ficou sem
     * vendedor perderia o conhecimento acumulado na prática — exatamente o que
     * a CAT-DOM-01 existiu para impedir.
     */
    public function test_item_sem_oferta_continua_encontrando_semelhantes(): void
    {
        [, $b] = $this->doisItensComConhecimentoEmComum();

        $orfao = Product::factory()->semOferta()->create([
            'name' => 'Manta de crochê artesanal para decoração',
        ]);
        app(AssociateProductKnowledge::class)($orfao, $this->match($orfao->name));

        $encontrados = app(FindSimilarProducts::class)($orfao)->pluck('product.id')->all();

        $this->assertContains($b->id, $encontrados);
    }

    /** O conhecimento não é apagado por perder vigência — ele só deixa de ser sugerido. */
    public function test_perder_vigencia_nao_apaga_o_conhecimento_do_item(): void
    {
        [, $b] = $this->doisItensComConhecimentoEmComum();

        $conceitosAntes = DB::table('catalog_product_knowledge')->where('product_id', $b->id)->count();
        $this->assertGreaterThan(0, $conceitosAntes);

        $b->offers()->update(['is_active' => false]);

        $this->assertSame(
            $conceitosAntes,
            DB::table('catalog_product_knowledge')->where('product_id', $b->id)->count(),
        );
    }

    /** O conhecimento é global: a referência atravessa lojistas de propósito. */
    public function test_similarity_crosses_expositores(): void
    {
        $this->conceito(KnowledgeEntryType::Technique, 'Crochê');

        $lojaA = Expositor::factory()->create();
        $lojaB = Expositor::factory()->create();

        $a = Product::factory()->doExpositor($lojaA)->create(['name' => 'Tapete de crochê']);
        $b = Product::factory()->doExpositor($lojaB)->create(['name' => 'Toalha de crochê']);

        $associar = app(AssociateProductKnowledge::class);
        $associar($a, $this->match($a->name));
        $associar($b, $this->match($b->name));

        $similares = app(FindSimilarProducts::class)($a);

        $this->assertCount(1, $similares);
        $this->assertSame($b->id, $similares->first()->product->id);
        $this->assertNotSame($a->expositor_id, $similares->first()->product->expositor_id);
    }

    public function test_similarity_respects_the_limit(): void
    {
        $this->conceito(KnowledgeEntryType::Technique, 'Crochê');
        $associar = app(AssociateProductKnowledge::class);

        $origem = Product::factory()->create(['name' => 'Tapete de crochê']);
        $associar($origem, $this->match($origem->name));

        foreach (range(1, 5) as $i) {
            $p = Product::factory()->create(['name' => "Peça {$i} de crochê"]);
            $associar($p, $this->match($p->name));
        }

        $this->assertCount(2, app(FindSimilarProducts::class)($origem, limit: 2));
    }

    // ── Performance ───────────────────────────────────────────────────────────

    /**
     * O custo não pode crescer com o catálogo.
     *
     * Trava o número de consultas, não o tempo: tempo varia com a máquina, mas
     * uma consulta por item dentro de um laço é defeito estrutural em qualquer
     * máquina.
     */
    public function test_matching_query_count_does_not_grow_with_the_knowledge_base(): void
    {
        foreach (range(1, 25) as $i) {
            $this->conceito(KnowledgeEntryType::Technique, "Técnica {$i}");
        }
        $this->conceito(KnowledgeEntryType::Technique, 'Crochê');

        DB::enableQueryLog();
        $this->match('Tapete artesanal de crochê');
        $consultas = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThanOrEqual(3, $consultas, "matcher usou {$consultas} consultas");
    }

    public function test_similarity_query_count_does_not_grow_with_the_catalog(): void
    {
        $this->conceito(KnowledgeEntryType::Technique, 'Crochê');
        $associar = app(AssociateProductKnowledge::class);

        $origem = Product::factory()->create(['name' => 'Tapete de crochê']);
        $associar($origem, $this->match($origem->name));

        foreach (range(1, 15) as $i) {
            $p = Product::factory()->create(['name' => "Peça {$i} de crochê"]);
            $associar($p, $this->match($p->name));
        }

        DB::enableQueryLog();
        app(FindSimilarProducts::class)($origem);
        $consultas = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThanOrEqual(3, $consultas, "similaridade usou {$consultas} consultas");
    }

    // ── Segurança: SEC-02 não é afetada ───────────────────────────────────────

    public function test_cat04_grants_no_write_access_to_foreign_products(): void
    {
        [$a, $b] = $this->doisItensComConhecimentoEmComum();

        $antes = $b->fresh()->toArray();
        app(FindSimilarProducts::class)($a);

        $this->assertEquals($antes, $b->fresh()->toArray());
    }
}
