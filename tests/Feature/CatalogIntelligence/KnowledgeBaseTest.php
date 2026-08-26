<?php

namespace Tests\Feature\CatalogIntelligence;

use App\CatalogIntelligence\Actions\AttachKnowledgeTerm;
use App\CatalogIntelligence\Actions\CreateOrUpdateKnowledge;
use App\CatalogIntelligence\Actions\RelateKnowledge;
use App\CatalogIntelligence\Enums\KnowledgeEntryType;
use App\CatalogIntelligence\Enums\KnowledgeRelationType;
use App\CatalogIntelligence\Enums\KnowledgeSource;
use App\CatalogIntelligence\Enums\KnowledgeStatus;
use App\CatalogIntelligence\Enums\KnowledgeTermType;
use App\CatalogIntelligence\Models\KnowledgeEntry;
use App\CatalogIntelligence\Models\KnowledgeRelation;
use App\CatalogIntelligence\Models\KnowledgeTerm;
use App\CatalogIntelligence\Support\KnowledgeNormalizer;
use App\Models\Product;
use Database\Seeders\CatalogKnowledgeSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Tests\TestCase;

/**
 * CAT-03 — base de conhecimento interna do catálogo.
 *
 * O que estes testes protegem, em ordem de importância: que o mesmo conceito
 * não vire dois; que conhecimento não assinado por uma pessoa não seja tratado
 * como verdade; e que a origem de cada afirmação continue distinguível.
 */
class KnowledgeBaseTest extends TestCase
{
    use RefreshDatabase;

    private function criar(): CreateOrUpdateKnowledge
    {
        return app(CreateOrUpdateKnowledge::class);
    }

    // ── Schema ────────────────────────────────────────────────────────────────

    public function test_knowledge_tables_exist(): void
    {
        $this->assertTrue(Schema::hasTable('catalog_knowledge_entries'));
        $this->assertTrue(Schema::hasTable('catalog_knowledge_terms'));
        $this->assertTrue(Schema::hasTable('catalog_knowledge_relations'));
        $this->assertTrue(Schema::hasTable('catalog_product_knowledge'));
    }

    public function test_entries_table_has_expected_columns(): void
    {
        foreach ([
            'type', 'name', 'normalized_name', 'description', 'status',
            'source', 'confidence', 'created_by', 'reviewed_by', 'reviewed_at',
        ] as $coluna) {
            $this->assertTrue(
                Schema::hasColumn('catalog_knowledge_entries', $coluna),
                "coluna ausente: {$coluna}"
            );
        }
    }

    /** A base não mora dentro de products — é o princípio arquitetural da trilha. */
    public function test_products_table_gained_no_knowledge_column(): void
    {
        foreach (['knowledge', 'embedding', 'keywords', 'tags', 'concepts'] as $proibida) {
            $this->assertFalse(
                Schema::hasColumn('products', $proibida),
                "products não deve ganhar coluna de inteligência: {$proibida}"
            );
        }
    }

    // ── Normalização ──────────────────────────────────────────────────────────

    /** @dataProvider variacoesDeCroche */
    public function test_normalizer_collapses_trivial_variations(string $entrada): void
    {
        $this->assertSame('croche', app(KnowledgeNormalizer::class)->normalize($entrada));
    }

    public static function variacoesDeCroche(): array
    {
        return [
            'canônico' => ['Crochê'],
            'minúsculo' => ['crochê'],
            'maiúsculo' => ['CROCHÊ'],
            'sem acento' => ['Croche'],
            'acento agudo' => ['croché'],
            'com espaços' => ['  Crochê  '],
            'com pontuação' => ['Crochê!'],
        ];
    }

    public function test_normalizer_collapses_internal_whitespace(): void
    {
        $this->assertSame(
            'ervas medicinais',
            app(KnowledgeNormalizer::class)->normalize("Ervas   \n medicinais")
        );
    }

    public function test_normalizer_treats_hyphen_as_separator(): void
    {
        $n = app(KnowledgeNormalizer::class);

        $this->assertSame($n->normalize('bem estar'), $n->normalize('bem-estar'));
    }

    public function test_normalizer_returns_empty_for_meaningless_input(): void
    {
        $n = app(KnowledgeNormalizer::class);

        $this->assertSame('', $n->normalize(''));
        $this->assertSame('', $n->normalize('   '));
        $this->assertSame('', $n->normalize('!!!'));
        $this->assertSame('', $n->normalize(null));
        $this->assertFalse($n->isUsable('!!!'));
    }

    public function test_display_name_keeps_accents(): void
    {
        $entry = ($this->criar())(KnowledgeEntryType::Technique, ' Crochê ', KnowledgeSource::HumanCurated);

        $this->assertSame('Crochê', $entry->name);
        $this->assertSame('croche', $entry->normalized_name);
    }

    // ── Unicidade ─────────────────────────────────────────────────────────────

    public function test_same_concept_written_differently_is_not_duplicated(): void
    {
        $criar = $this->criar();

        $a = $criar(KnowledgeEntryType::Technique, 'Crochê', KnowledgeSource::HumanCurated);
        $b = $criar(KnowledgeEntryType::Technique, 'CROCHE', KnowledgeSource::HumanCurated);
        $c = $criar(KnowledgeEntryType::Technique, '  croché ', KnowledgeSource::HumanCurated);

        $this->assertSame($a->id, $b->id);
        $this->assertSame($a->id, $c->id);
        $this->assertSame(1, KnowledgeEntry::count());
    }

    /** A garantia é do banco, não de um `if` — é isso que sobrevive a concorrência. */
    public function test_database_rejects_duplicate_normalized_name_per_type(): void
    {
        ($this->criar())(KnowledgeEntryType::Technique, 'Crochê', KnowledgeSource::HumanCurated);

        $this->expectException(QueryException::class);

        $duplicata = new KnowledgeEntry([
            'type' => KnowledgeEntryType::Technique,
            'name' => 'Croche',
            'status' => KnowledgeStatus::Draft,
            'source' => KnowledgeSource::Derived,
        ]);
        $duplicata->normalized_name = 'croche';
        $duplicata->save();
    }

    /** A mesma palavra pode ser conceitos diferentes: cerâmica é técnica e material. */
    public function test_same_name_in_different_types_are_distinct_concepts(): void
    {
        $criar = $this->criar();

        $tecnica = $criar(KnowledgeEntryType::Technique, 'Cerâmica', KnowledgeSource::HumanCurated);
        $material = $criar(KnowledgeEntryType::Material, 'Cerâmica', KnowledgeSource::HumanCurated);

        $this->assertNotSame($tecnica->id, $material->id);
        $this->assertSame(2, KnowledgeEntry::count());
    }

    public function test_empty_name_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ($this->criar())(KnowledgeEntryType::Technique, '   ', KnowledgeSource::HumanCurated);
    }

    // ── Proveniência e governança ─────────────────────────────────────────────

    public function test_human_sources_are_born_approved(): void
    {
        $criar = $this->criar();

        $curado = $criar(KnowledgeEntryType::Technique, 'Bordado', KnowledgeSource::HumanCurated);
        $semeado = $criar(KnowledgeEntryType::Technique, 'Tecelagem', KnowledgeSource::Seed);

        $this->assertSame(KnowledgeStatus::Approved, $curado->status);
        $this->assertSame(KnowledgeStatus::Approved, $semeado->status);
    }

    /** O núcleo da governança: cadastro não vira conhecimento aprovado sozinho. */
    public function test_non_human_sources_are_born_as_draft(): void
    {
        $criar = $this->criar();

        foreach ([KnowledgeSource::Derived, KnowledgeSource::ApprovedListing, KnowledgeSource::ExternalAi] as $i => $origem) {
            $entry = $criar(KnowledgeEntryType::Technique, 'Conceito '.$i, $origem);

            $this->assertSame(KnowledgeStatus::Draft, $entry->status, $origem->value);
            $this->assertFalse($entry->isUsable());
        }
    }

    public function test_non_human_source_cannot_be_forced_to_approved(): void
    {
        $this->expectException(InvalidArgumentException::class);

        ($this->criar())(
            KnowledgeEntryType::Technique,
            'Tentativa',
            KnowledgeSource::ExternalAi,
            KnowledgeStatus::Approved
        );
    }

    public function test_revisiting_a_concept_never_promotes_its_status(): void
    {
        $criar = $this->criar();

        $entry = $criar(KnowledgeEntryType::Technique, 'Macramê', KnowledgeSource::Derived);
        $this->assertSame(KnowledgeStatus::Draft, $entry->status);

        $criar(KnowledgeEntryType::Technique, 'macrame', KnowledgeSource::Derived, description: 'Nós decorativos.');

        $this->assertSame(KnowledgeStatus::Draft, $entry->fresh()->status);
    }

    public function test_lower_trust_source_does_not_overwrite_human_description(): void
    {
        $criar = $this->criar();

        $criar(KnowledgeEntryType::Technique, 'Crochê', KnowledgeSource::HumanCurated, description: 'Texto do curador.');
        $criar(KnowledgeEntryType::Technique, 'Crochê', KnowledgeSource::ExternalAi, description: 'Texto de IA.');

        $entry = KnowledgeEntry::first();
        $this->assertSame('Texto do curador.', $entry->description);
        $this->assertSame(KnowledgeSource::HumanCurated, $entry->source);
    }

    public function test_higher_trust_source_may_correct_a_weaker_description(): void
    {
        $criar = $this->criar();

        $criar(KnowledgeEntryType::Technique, 'Crochê', KnowledgeSource::Derived, description: 'Texto deduzido.');
        $criar(KnowledgeEntryType::Technique, 'Crochê', KnowledgeSource::HumanCurated, description: 'Texto do curador.');

        $entry = KnowledgeEntry::first();
        $this->assertSame('Texto do curador.', $entry->description);
        $this->assertSame(KnowledgeSource::HumanCurated, $entry->source);
    }

    public function test_weaker_source_may_fill_a_missing_description(): void
    {
        $criar = $this->criar();

        $criar(KnowledgeEntryType::Technique, 'Crochê', KnowledgeSource::HumanCurated);
        $criar(KnowledgeEntryType::Technique, 'Crochê', KnowledgeSource::Derived, description: 'Preenchendo lacuna.');

        $entry = KnowledgeEntry::first();
        $this->assertSame('Preenchendo lacuna.', $entry->description);
        // A bandeira continua sendo a de quem tem mais autoridade.
        $this->assertSame(KnowledgeSource::HumanCurated, $entry->source);
    }

    public function test_source_trust_order_is_explicit(): void
    {
        $this->assertTrue(KnowledgeSource::HumanCurated->outranks(KnowledgeSource::Seed));
        $this->assertTrue(KnowledgeSource::Seed->outranks(KnowledgeSource::ApprovedListing));
        $this->assertTrue(KnowledgeSource::ApprovedListing->outranks(KnowledgeSource::Derived));
        $this->assertTrue(KnowledgeSource::Derived->outranks(KnowledgeSource::ExternalAi));
        $this->assertFalse(KnowledgeSource::ExternalAi->outranks(KnowledgeSource::HumanCurated));
    }

    /** `confidence` fica nula na CAT-03: a confiança é ordinal, não numérica. */
    public function test_confidence_is_not_auto_filled(): void
    {
        $entry = ($this->criar())(KnowledgeEntryType::Technique, 'Crochê', KnowledgeSource::HumanCurated);

        $this->assertNull($entry->confidence);
    }

    public function test_usable_scope_returns_only_approved(): void
    {
        KnowledgeEntry::factory()->aprovado()->chamado('Aprovado')->create();
        KnowledgeEntry::factory()->chamado('Rascunho')->create();
        KnowledgeEntry::factory()->chamado('Rejeitado')->create(['status' => KnowledgeStatus::Rejected->value]);
        KnowledgeEntry::factory()->chamado('Inativo')->create(['status' => KnowledgeStatus::Inactive->value]);

        $usaveis = KnowledgeEntry::usable()->pluck('name');

        $this->assertSame(['Aprovado'], $usaveis->all());
    }

    // ── Termos ────────────────────────────────────────────────────────────────

    public function test_terms_attach_to_a_concept(): void
    {
        $entry = ($this->criar())(KnowledgeEntryType::Attribute, 'Feito à mão', KnowledgeSource::HumanCurated);
        $anexar = app(AttachKnowledgeTerm::class);

        $anexar($entry, 'artesanal', KnowledgeTermType::Synonym);
        $anexar($entry, 'handmade', KnowledgeTermType::CommercialTerm);

        $this->assertCount(2, $entry->fresh()->terms);
        $this->assertSame($entry->id, KnowledgeTerm::first()->entry->id);
    }

    public function test_term_is_not_duplicated_by_spelling(): void
    {
        $entry = ($this->criar())(KnowledgeEntryType::Attribute, 'Feito à mão', KnowledgeSource::HumanCurated);
        $anexar = app(AttachKnowledgeTerm::class);

        $a = $anexar($entry, 'Artesanal', KnowledgeTermType::Synonym);
        $b = $anexar($entry, '  artesanal ', KnowledgeTermType::Synonym);

        $this->assertSame($a->id, $b->id);
        $this->assertSame(1, KnowledgeTerm::count());
    }

    /** O mesmo termo pode servir a conceitos diferentes — a unicidade é por conceito. */
    public function test_same_term_may_belong_to_different_concepts(): void
    {
        $criar = $this->criar();
        $anexar = app(AttachKnowledgeTerm::class);

        $a = $criar(KnowledgeEntryType::Technique, 'Cerâmica', KnowledgeSource::HumanCurated);
        $b = $criar(KnowledgeEntryType::Material, 'Barro', KnowledgeSource::HumanCurated);

        $anexar($a, 'argila', KnowledgeTermType::Keyword);
        $anexar($b, 'argila', KnowledgeTermType::Synonym);

        $this->assertSame(2, KnowledgeTerm::where('normalized_term', 'argila')->count());
    }

    public function test_empty_term_is_rejected(): void
    {
        $entry = ($this->criar())(KnowledgeEntryType::Technique, 'Crochê', KnowledgeSource::HumanCurated);

        $this->expectException(InvalidArgumentException::class);
        app(AttachKnowledgeTerm::class)($entry, '  ', KnowledgeTermType::Synonym);
    }

    // ── Relações ──────────────────────────────────────────────────────────────

    public function test_relations_link_two_concepts_directionally(): void
    {
        $criar = $this->criar();
        $ligar = app(RelateKnowledge::class);

        $croche = $criar(KnowledgeEntryType::Technique, 'Crochê', KnowledgeSource::HumanCurated);
        $artesanato = $criar(KnowledgeEntryType::Theme, 'Artesanato', KnowledgeSource::HumanCurated);

        $ligar($croche, $artesanato, KnowledgeRelationType::TechniqueOf);

        $this->assertCount(1, $croche->fresh()->outgoingRelations);
        $this->assertCount(0, $croche->fresh()->incomingRelations);
        $this->assertCount(1, $artesanato->fresh()->incomingRelations);

        $relacao = KnowledgeRelation::first();
        $this->assertSame($croche->id, $relacao->from->id);
        $this->assertSame($artesanato->id, $relacao->to->id);
    }

    public function test_relation_is_not_duplicated(): void
    {
        $criar = $this->criar();
        $ligar = app(RelateKnowledge::class);

        $a = $criar(KnowledgeEntryType::Technique, 'Crochê', KnowledgeSource::HumanCurated);
        $b = $criar(KnowledgeEntryType::Theme, 'Artesanato', KnowledgeSource::HumanCurated);

        $r1 = $ligar($a, $b, KnowledgeRelationType::TechniqueOf);
        $r2 = $ligar($a, $b, KnowledgeRelationType::TechniqueOf);

        $this->assertSame($r1->id, $r2->id);
        $this->assertSame(1, KnowledgeRelation::count());
    }

    public function test_same_pair_may_hold_different_relation_types(): void
    {
        $criar = $this->criar();
        $ligar = app(RelateKnowledge::class);

        $a = $criar(KnowledgeEntryType::Technique, 'Crochê', KnowledgeSource::HumanCurated);
        $b = $criar(KnowledgeEntryType::ProductType, 'Bolsa', KnowledgeSource::HumanCurated);

        $ligar($a, $b, KnowledgeRelationType::UsedIn);
        $ligar($a, $b, KnowledgeRelationType::RelatedTo);

        $this->assertSame(2, KnowledgeRelation::count());
    }

    public function test_concept_cannot_relate_to_itself(): void
    {
        $entry = ($this->criar())(KnowledgeEntryType::Technique, 'Crochê', KnowledgeSource::HumanCurated);

        $this->expectException(InvalidArgumentException::class);
        app(RelateKnowledge::class)($entry, $entry, KnowledgeRelationType::RelatedTo);
    }

    public function test_deleting_a_concept_removes_its_terms_and_relations(): void
    {
        $criar = $this->criar();
        $croche = $criar(KnowledgeEntryType::Technique, 'Crochê', KnowledgeSource::HumanCurated);
        $artesanato = $criar(KnowledgeEntryType::Theme, 'Artesanato', KnowledgeSource::HumanCurated);

        app(AttachKnowledgeTerm::class)($croche, 'crochetar', KnowledgeTermType::Synonym);
        app(RelateKnowledge::class)($croche, $artesanato, KnowledgeRelationType::TechniqueOf);

        $croche->delete();

        $this->assertSame(0, KnowledgeTerm::count());
        $this->assertSame(0, KnowledgeRelation::count());
        $this->assertSame(1, KnowledgeEntry::count());
    }

    // ── Ponte com o catálogo ──────────────────────────────────────────────────

    public function test_product_and_knowledge_can_be_linked(): void
    {
        $product = Product::factory()->create();
        $entry = ($this->criar())(KnowledgeEntryType::Technique, 'Crochê', KnowledgeSource::HumanCurated);

        $entry->products()->attach($product->id, [
            'source' => KnowledgeSource::HumanCurated->value,
            'confidence' => null,
        ]);

        $this->assertTrue($entry->fresh()->products->contains($product->id));
        $this->assertSame(
            KnowledgeSource::HumanCurated->value,
            $entry->fresh()->products->first()->pivot->source
        );
    }

    public function test_product_knowledge_link_is_unique(): void
    {
        $product = Product::factory()->create();
        $entry = ($this->criar())(KnowledgeEntryType::Technique, 'Crochê', KnowledgeSource::HumanCurated);

        $entry->products()->attach($product->id, ['source' => KnowledgeSource::Seed->value]);

        $this->expectException(QueryException::class);
        $entry->products()->attach($product->id, ['source' => KnowledgeSource::Derived->value]);
    }

    /** A CAT-03 cria a capacidade, não a inferência. Nenhum produto sai associado. */
    public function test_cat03_infers_no_association_from_product_text(): void
    {
        Product::factory()->create(['name' => 'Tapete de crochê artesanal']);

        $this->seed(CatalogKnowledgeSeeder::class);

        $this->assertSame(0, \DB::table('catalog_product_knowledge')->count());
    }

    // ── Seeder ────────────────────────────────────────────────────────────────

    public function test_seeder_creates_the_initial_base(): void
    {
        $this->seed(CatalogKnowledgeSeeder::class);

        $this->assertSame(28, KnowledgeEntry::count());
        $this->assertGreaterThan(0, KnowledgeTerm::count());
        $this->assertGreaterThan(0, KnowledgeRelation::count());
        $this->assertSame(28, KnowledgeEntry::usable()->count());
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seed(CatalogKnowledgeSeeder::class);

        $entries = KnowledgeEntry::count();
        $terms = KnowledgeTerm::count();
        $relations = KnowledgeRelation::count();

        $this->seed(CatalogKnowledgeSeeder::class);
        $this->seed(CatalogKnowledgeSeeder::class);

        $this->assertSame($entries, KnowledgeEntry::count());
        $this->assertSame($terms, KnowledgeTerm::count());
        $this->assertSame($relations, KnowledgeRelation::count());
    }

    public function test_seeder_does_not_touch_products(): void
    {
        $product = Product::factory()->create(['name' => 'Bolsa Tecida Artesanal']);
        $antes = $product->fresh()->toArray();

        $this->seed(CatalogKnowledgeSeeder::class);

        $this->assertSame(1, Product::count());
        $this->assertEquals($antes, $product->fresh()->toArray());
    }

    public function test_seeded_knowledge_carries_seed_provenance(): void
    {
        $this->seed(CatalogKnowledgeSeeder::class);

        $this->assertSame(28, KnowledgeEntry::where('source', KnowledgeSource::Seed)->count());
        $this->assertSame(0, KnowledgeEntry::where('source', KnowledgeSource::ExternalAi)->count());
    }
}
