<?php

namespace Tests\Feature\CatalogIntelligence;

use App\CatalogIntelligence\Actions\AssociateProductKnowledge;
use App\CatalogIntelligence\Actions\AttachKnowledgeTerm;
use App\CatalogIntelligence\Actions\CreateOrUpdateKnowledge;
use App\CatalogIntelligence\Actions\GenerateListingSuggestion;
use App\CatalogIntelligence\Actions\MatchProductKnowledge;
use App\CatalogIntelligence\DTOs\ListingContext;
use App\CatalogIntelligence\DTOs\ListingSuggestion;
use App\CatalogIntelligence\DTOs\ProductKnowledgeInput;
use App\CatalogIntelligence\Enums\KnowledgeEntryType;
use App\CatalogIntelligence\Enums\KnowledgeSource;
use App\CatalogIntelligence\Enums\KnowledgeStatus;
use App\CatalogIntelligence\Enums\KnowledgeTermType;
use App\CatalogIntelligence\Enums\ListingGap;
use App\CatalogIntelligence\Enums\SuggestionSource;
use App\CatalogIntelligence\Models\KnowledgeEntry;
use App\Enums\ItemType;
use App\Models\ContentCategory;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * CAT-05D — o assistente de conteúdo interno.
 * CAT-05E — antialucinação, palavras-chave por termo e pedidos legíveis.
 *
 * Duas garantias interessam mais que o texto produzido: **gerar não escreve** e
 * **nada entra no texto que não estivesse no contexto**. O resto é composição,
 * e composição é conferível.
 *
 * Nenhum caso depende do banco de desenvolvimento. A P-1 (backfill, 0
 * associações) segue adiada para a CAT-05H por decisão humana, e os cenários
 * são montados por factory e pelas Actions, como a CAT-05C fez.
 */
class ListingAssistantTest extends TestCase
{
    use RefreshDatabase;

    private function conceito(
        string $nome,
        KnowledgeEntryType $tipo = KnowledgeEntryType::Technique,
        ?string $descricaoCurada = null,
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

    private function assistente(): GenerateListingSuggestion
    {
        return app(GenerateListingSuggestion::class);
    }

    private function contexto(string $nome, ?string $resumo = null, ?string $descricao = null): ListingContext
    {
        return ListingContext::paraItemNovo(
            ItemType::Produto,
            $nome,
            shortDescription: $resumo,
            description: $descricao,
        );
    }

    // ── A garantia central: gerar não é salvar (D-CAT-05B-1) ──────────────────

    public function test_gerar_nao_escreve_nada_em_lugar_nenhum(): void
    {
        $this->conceito('Crochê', descricaoCurada: 'Técnica de tecer com agulha única.');
        $produto = Product::factory()->create(['name' => 'Tapete de crochê']);

        $antesProduto = $produto->fresh()->toArray();
        $antesPivot = DB::table('catalog_product_knowledge')->count();
        $antesConceitos = KnowledgeEntry::count();
        $antesOfertas = DB::table('product_offers')->get()->toArray();

        $this->assistente()(ListingContext::deProduct($produto), $produto);

        $this->assertSame($antesProduto, $produto->fresh()->toArray());
        $this->assertSame($antesPivot, DB::table('catalog_product_knowledge')->count());
        $this->assertSame($antesConceitos, KnowledgeEntry::count());
        $this->assertEquals($antesOfertas, DB::table('product_offers')->get()->toArray());
    }

    /**
     * O assistente tem em mãos exatamente os candidatos de que
     * `AssociateProductKnowledge` precisaria — e não a chama. Sugerir texto e
     * afirmar conhecimento são atos diferentes.
     */
    public function test_gerar_nao_associa_conhecimento_ao_produto(): void
    {
        $this->conceito('Crochê');
        $produto = Product::factory()->create(['name' => 'Tapete de crochê artesanal']);

        $sugestao = $this->assistente()(ListingContext::deProduct($produto), $produto);

        $this->assertNotEmpty($sugestao->keywords, 'o cenário exige conceito encontrado');
        $this->assertDatabaseCount('catalog_product_knowledge', 0);
    }

    // ── Fonte e ausência de provider (D-CAT-05B-4) ────────────────────────────

    public function test_a_fonte_e_sempre_interna_nesta_fase(): void
    {
        $this->conceito('Crochê');

        $sugestao = $this->assistente()($this->contexto('Tapete de crochê'));

        $this->assertSame(SuggestionSource::Internal, $sugestao->source);
        $this->assertTrue($sugestao->source->isInternal());
    }

    public function test_nenhuma_interface_de_provider_externo_existe(): void
    {
        foreach (['CatalogAiProvider', 'FakeCatalogAiProvider', 'NullCatalogAiProvider', 'EmbeddingProvider'] as $classe) {
            $this->assertFalse(
                class_exists("App\\CatalogIntelligence\\Contracts\\{$classe}")
                || interface_exists("App\\CatalogIntelligence\\Contracts\\{$classe}")
                || class_exists("App\\CatalogIntelligence\\Providers\\{$classe}"),
                "{$classe} pertence à CAT-06 e não deve existir ainda",
            );
        }
    }

    public function test_o_assistente_nao_depende_de_nenhum_provider(): void
    {
        $construtor = (new \ReflectionClass(GenerateListingSuggestion::class))->getConstructor();

        $tipos = collect($construtor->getParameters())
            ->map(fn (\ReflectionParameter $p) => (string) $p->getType())
            ->implode(' ');

        $this->assertStringNotContainsString('Provider', $tipos);
        $this->assertStringNotContainsString('Http', $tipos);
    }

    // ── Antialucinação estrutural: nada além do contexto ──────────────────────

    /**
     * O item não diz material nenhum, e a base tem um conceito de material que
     * o texto **não** menciona. Ele não pode aparecer na sugestão.
     */
    public function test_conceito_que_o_texto_nao_menciona_nao_entra_na_sugestao(): void
    {
        $this->conceito('Crochê', descricaoCurada: 'Técnica de tecer com agulha única.');
        $this->conceito('Couro', KnowledgeEntryType::Material, 'Material de origem animal.');

        $sugestao = $this->assistente()($this->contexto('Tapete de crochê'));

        $serializado = json_encode($sugestao->toArray());
        $this->assertStringNotContainsString('Couro', $serializado);
        $this->assertStringNotContainsString('origem animal', $serializado);
        $this->assertContains('Crochê', $sugestao->keywords);
    }

    public function test_conceito_nao_aprovado_nao_alimenta_a_sugestao(): void
    {
        $entry = $this->conceito('Crochê', descricaoCurada: 'Técnica de tecer.');
        $entry->update(['status' => KnowledgeStatus::Draft]);

        $sugestao = $this->assistente()($this->contexto('Tapete de crochê'));

        $this->assertFalse($sugestao->temAlgoAPropor());
        $this->assertSame([], $sugestao->keywords);
    }

    public function test_atributo_nao_informado_nao_aparece_no_texto(): void
    {
        $this->conceito('Crochê', descricaoCurada: 'Técnica de tecer com agulha única.');

        $sugestao = $this->assistente()($this->contexto('Tapete de crochê'));

        $serializado = json_encode($sugestao->toArray());
        foreach (['algodão', 'lã', 'lavável', 'importado', 'certificado'] as $inventado) {
            $this->assertStringNotContainsString($inventado, $serializado);
        }
    }

    // ── Composição ────────────────────────────────────────────────────────────

    public function test_resumo_e_composto_a_partir_dos_conceitos_encontrados(): void
    {
        $this->conceito('Crochê');
        $this->conceito('Feito à mão', KnowledgeEntryType::Attribute);

        $sugestao = $this->assistente()($this->contexto('Tapete de crochê feito à mão'));

        $this->assertNotNull($sugestao->shortDescription);
        $this->assertStringStartsWith('Tapete de crochê feito à mão.', $sugestao->shortDescription);
        $this->assertStringContainsString('Crochê', $sugestao->shortDescription);
        $this->assertStringContainsString('Feito à mão', $sugestao->shortDescription);
    }

    public function test_resumo_cabe_no_limite_do_campo(): void
    {
        for ($i = 1; $i <= 8; $i++) {
            $this->conceito("Conceito numero {$i} com nome bastante longo para ocupar espaco", KnowledgeEntryType::Context);
        }

        $nome = str_repeat('Item de nome muito longo ', 15);
        $texto = $nome;
        for ($i = 1; $i <= 8; $i++) {
            $texto .= " Conceito numero {$i} com nome bastante longo para ocupar espaco";
        }

        $sugestao = $this->assistente()($this->contexto(trim($texto)));

        if ($sugestao->shortDescription !== null) {
            $this->assertLessThanOrEqual(500, mb_strlen($sugestao->shortDescription));
        } else {
            $this->assertTrue(true, 'nulo é resposta válida quando nada cabe');
        }
    }

    public function test_descricao_usa_o_texto_curado_do_conceito(): void
    {
        $this->conceito('Crochê', descricaoCurada: 'Técnica de tecer fios com agulha única, feita à mão.');

        $sugestao = $this->assistente()($this->contexto('Tapete de crochê'));

        $this->assertNotNull($sugestao->description);
        $this->assertStringContainsString('Técnica de tecer fios com agulha única', $sugestao->description);
    }

    public function test_conceito_sem_descricao_curada_nao_gera_explicacao_inventada(): void
    {
        $this->conceito('Crochê');

        $sugestao = $this->assistente()($this->contexto('Tapete de crochê'));

        $this->assertNull($sugestao->description, 'sem texto curado não há descrição a propor');
        $this->assertNotNull($sugestao->shortDescription, 'o resumo ainda é possível pelo nome do conceito');
    }

    public function test_a_categoria_abre_a_descricao_quando_existe(): void
    {
        $this->conceito('Crochê', descricaoCurada: 'Técnica de tecer.');
        $categoria = ContentCategory::create(['name' => 'Artesanato', 'eixo' => 'produto']);
        $produto = Product::factory()->create([
            'name' => 'Tapete de crochê',
            'category_id' => $categoria->id,
            // A factory preenche `description` por padrão, e campo preenchido
            // não recebe proposta — o cenário aqui é o do item sem descrição.
            'description' => null,
        ]);

        $sugestao = $this->assistente()(ListingContext::deProduct($produto->load('category')));

        $this->assertStringContainsString('Artesanato', $sugestao->description);
    }

    public function test_palavras_chave_incluem_os_conceitos_aprovados(): void
    {
        $this->conceito('Crochê');
        $this->conceito('Feito à mão', KnowledgeEntryType::Attribute);

        $sugestao = $this->assistente()($this->contexto('Tapete de crochê feito à mão'));

        $this->assertContains('Crochê', $sugestao->keywords);
        $this->assertContains('Feito à mão', $sugestao->keywords);
    }

    // ── P-4: quais termos viram palavra-chave (CAT-05E) ───────────────────────

    /**
     * O caso que motivou a decisão: "Costura" não alcança quem procura por
     * "ajuste de roupa", que é o termo comercial cadastrado para ela.
     */
    public function test_termo_comercial_entra_nas_palavras_chave(): void
    {
        $costura = $this->conceito('Costura');
        $this->termo($costura, 'ajuste de roupa', KnowledgeTermType::CommercialTerm);

        $sugestao = $this->assistente()($this->contexto('Serviço de costura sob medida'));

        $this->assertContains('Costura', $sugestao->keywords);
        $this->assertContains('ajuste de roupa', $sugestao->keywords);
    }

    public function test_sinonimo_entra_nas_palavras_chave(): void
    {
        $barro = $this->conceito('Barro', KnowledgeEntryType::Material);
        $this->termo($barro, 'argila', KnowledgeTermType::Synonym);

        $sugestao = $this->assistente()($this->contexto('Tigela de barro nordestina'));

        $this->assertContains('Barro', $sugestao->keywords);
        $this->assertContains('argila', $sugestao->keywords);
    }

    /**
     * Sete dos oito `alias` da base real são a grafia sem acento do próprio
     * nome canônico. Como palavra-chave produziriam "Crochê" e "croche" lado a
     * lado — e não acrescentam nem ao casamento, que já normaliza acentos.
     */
    public function test_grafia_alternativa_nao_entra_nas_palavras_chave(): void
    {
        $croche = $this->conceito('Crochê');
        $this->termo($croche, 'croche', KnowledgeTermType::Alias);

        $sugestao = $this->assistente()($this->contexto('Tapete de crochê'));

        $this->assertContains('Crochê', $sugestao->keywords);
        $this->assertNotContains('croche', $sugestao->keywords);
    }

    /** O tipo existe no enum e nenhum registro o usa — não se decide por ele agora. */
    public function test_termo_do_tipo_keyword_nao_entra_por_ora(): void
    {
        $croche = $this->conceito('Crochê');
        $this->termo($croche, 'tapetaria', KnowledgeTermType::Keyword);

        $sugestao = $this->assistente()($this->contexto('Tapete de crochê'));

        $this->assertNotContains('tapetaria', $sugestao->keywords);
    }

    public function test_o_nome_canonico_vem_antes_dos_termos(): void
    {
        $costura = $this->conceito('Costura');
        $this->termo($costura, 'ajuste de roupa', KnowledgeTermType::CommercialTerm);

        $keywords = $this->assistente()($this->contexto('Serviço de costura'))->keywords;

        $this->assertLessThan(
            array_search('ajuste de roupa', $keywords, true),
            array_search('Costura', $keywords, true),
        );
    }

    public function test_palavras_chave_nao_repetem(): void
    {
        $croche = $this->conceito('Crochê');
        $this->termo($croche, 'crochetar', KnowledgeTermType::Synonym);
        $trico = $this->conceito('Tricô');
        $this->termo($trico, 'crochetar', KnowledgeTermType::Synonym);

        $keywords = $this->assistente()($this->contexto('Peça de crochê e tricô'))->keywords;

        $this->assertSame(array_values(array_unique($keywords)), $keywords);
    }

    // ── missing_information em linguagem de lojista (CAT-05E) ─────────────────

    /** A citação da §3.4: "em vez de inventar material, devolve 'informe o material'". */
    public function test_o_que_falta_e_pedido_em_portugues_e_nao_nome_de_campo(): void
    {
        $this->conceito('Crochê', descricaoCurada: 'Técnica de tecer.');

        $pedidos = $this->assistente()($this->contexto('Tapete de crochê'))->missingInformation;

        foreach (['short_description', 'description', 'category', 'attributes', 'knowledge'] as $tecnico) {
            $this->assertNotContains($tecnico, $pedidos, "nome de campo cru vazou: {$tecnico}");
        }

        $this->assertContains(ListingGap::Attributes->pedido(), $pedidos);
        $this->assertStringContainsString('material', ListingGap::Attributes->pedido());
    }

    public function test_toda_lacuna_tem_pedido_e_nenhum_e_vazio(): void
    {
        foreach (ListingGap::cases() as $lacuna) {
            $this->assertNotSame('', trim($lacuna->pedido()), "{$lacuna->value} sem pedido");
        }
    }

    /**
     * Pedir "escreva um resumo" ao lado de um resumo pronto é ruído, e ruído
     * faz o lojista desconfiar dos pedidos que ele precisa mesmo atender.
     */
    public function test_lacuna_que_a_sugestao_preenche_nao_vira_pedido(): void
    {
        $this->conceito('Crochê', descricaoCurada: 'Técnica de tecer.');

        $sugestao = $this->assistente()($this->contexto('Tapete de crochê'));

        $this->assertNotNull($sugestao->shortDescription, 'o cenário exige resumo proposto');
        $this->assertNotNull($sugestao->description, 'o cenário exige descrição proposta');

        $this->assertNotContains(ListingGap::ShortDescription->pedido(), $sugestao->missingInformation);
        $this->assertNotContains(ListingGap::Description->pedido(), $sugestao->missingInformation);
    }

    /** O que o assistente não pode preencher continua sendo pedido, sempre. */
    public function test_lacuna_que_depende_de_pessoa_continua_sendo_pedida(): void
    {
        $this->conceito('Crochê', descricaoCurada: 'Técnica de tecer.');

        $pedidos = $this->assistente()($this->contexto('Tapete de crochê'))->missingInformation;

        $this->assertContains(ListingGap::Attributes->pedido(), $pedidos);
        $this->assertContains(ListingGap::Category->pedido(), $pedidos);
    }

    public function test_campo_sem_proposta_volta_a_ser_pedido(): void
    {
        // Conceito sem descrição curada: não há descrição a propor, então a
        // lacuna permanece e o pedido reaparece.
        $this->conceito('Crochê');

        $sugestao = $this->assistente()($this->contexto('Tapete de crochê'));

        $this->assertNull($sugestao->description);
        $this->assertContains(ListingGap::Description->pedido(), $sugestao->missingInformation);
    }

    public function test_sugestao_vazia_pede_tudo_em_linguagem_de_lojista(): void
    {
        $sugestao = $this->assistente()($this->contexto('Item que a base não alcança'));

        $this->assertFalse($sugestao->temAlgoAPropor());
        $this->assertContains(ListingGap::Knowledge->pedido(), $sugestao->missingInformation);
        $this->assertContains(ListingGap::ShortDescription->pedido(), $sugestao->missingInformation);
    }

    // ── Não sobrescrever o que o humano escreveu ──────────────────────────────

    public function test_campo_ja_preenchido_nao_recebe_proposta(): void
    {
        $this->conceito('Crochê', descricaoCurada: 'Técnica de tecer.');

        $sugestao = $this->assistente()($this->contexto(
            'Tapete de crochê',
            resumo: 'Resumo escrito pela lojista.',
            descricao: 'Descrição escrita pela lojista.',
        ));

        $this->assertNull($sugestao->shortDescription);
        $this->assertNull($sugestao->description);
        $this->assertNotEmpty($sugestao->keywords, 'as palavras-chave continuam sendo úteis');
    }

    public function test_o_nome_nunca_e_proposto_no_caminho_interno(): void
    {
        $this->conceito('Crochê', descricaoCurada: 'Técnica de tecer.');

        $sugestao = $this->assistente()($this->contexto('tapete'));

        $this->assertNull($sugestao->suggestedName);
        $this->assertNotContains('name', $sugestao->camposPropostos());
    }

    // ── Sugestão vazia é resposta, não falha ──────────────────────────────────

    public function test_base_sem_conceito_devolve_sugestao_vazia_e_nao_quebra(): void
    {
        $sugestao = $this->assistente()($this->contexto('Item que a base não alcança'));

        $this->assertInstanceOf(ListingSuggestion::class, $sugestao);
        $this->assertFalse($sugestao->temAlgoAPropor());
        $this->assertSame([], $sugestao->camposPropostos());
        $this->assertNotEmpty($sugestao->missingInformation, 'o que falta continua sendo dito');
    }

    public function test_a_sugestao_sempre_diz_o_que_falta(): void
    {
        $this->conceito('Crochê', descricaoCurada: 'Técnica de tecer.');

        $sugestao = $this->assistente()($this->contexto('Tapete de crochê'));

        // Revisto na CAT-05E: o que sai já é pedido, não nome de campo, e o
        // resumo e a descrição saíram da lista porque a sugestão os preenche.
        $this->assertNotEmpty($sugestao->missingInformation);
        $this->assertContains(ListingGap::Attributes->pedido(), $sugestao->missingInformation);
        $this->assertContains(ListingGap::Category->pedido(), $sugestao->missingInformation);
    }

    // ── Item ainda não salvo × item do catálogo ───────────────────────────────

    public function test_funciona_para_item_que_ainda_nao_existe(): void
    {
        $this->conceito('Crochê', descricaoCurada: 'Técnica de tecer.');

        $sugestao = $this->assistente()($this->contexto('Tapete de crochê'));

        $this->assertTrue($sugestao->temAlgoAPropor());
        $this->assertSame(0, Product::count(), 'sugerir não cria item');
    }

    public function test_semelhantes_entram_no_contexto_quando_o_item_esta_salvo(): void
    {
        $this->conceito('Crochê');

        $origem = Product::factory()->create(['name' => 'Tapete de crochê']);
        $vizinho = Product::factory()->create(['name' => 'Toalha de crochê']);

        $associar = app(AssociateProductKnowledge::class);
        foreach ([$origem, $vizinho] as $p) {
            $associar($p, app(MatchProductKnowledge::class)(new ProductKnowledgeInput(name: $p->name)));
        }

        [, $contexto] = $this->assistente()->comContexto(ListingContext::deProduct($origem), $origem);

        $this->assertCount(1, $contexto->similarItems);
        $this->assertSame($vizinho->name, $contexto->similarItems[0]['name']);
    }

    public function test_sem_produto_salvo_nao_ha_semelhantes_e_isso_nao_quebra(): void
    {
        $this->conceito('Crochê', descricaoCurada: 'Técnica de tecer.');
        Product::factory()->create(['name' => 'Toalha de crochê']);

        [$sugestao, $contexto] = $this->assistente()->comContexto($this->contexto('Tapete de crochê'));

        $this->assertSame([], $contexto->similarItems);
        $this->assertTrue($sugestao->temAlgoAPropor());
    }

    /** A entrada volta junto com a saída — sem ela a CAT-07 não teria o que auditar. */
    public function test_o_contexto_que_produziu_a_sugestao_e_devolvido(): void
    {
        $this->conceito('Crochê', descricaoCurada: 'Técnica de tecer.');

        [$sugestao, $contexto] = $this->assistente()->comContexto($this->contexto('Tapete de crochê'));

        $this->assertInstanceOf(ListingSuggestion::class, $sugestao);
        $this->assertInstanceOf(ListingContext::class, $contexto);
        $this->assertNotEmpty($contexto->knowledge);
    }

    // ── `confidence` fica nula, por decisão ───────────────────────────────────

    public function test_confidence_nao_e_inventada(): void
    {
        $this->conceito('Crochê', descricaoCurada: 'Técnica de tecer.');

        $sugestao = $this->assistente()($this->contexto('Tapete de crochê'));

        $this->assertNull($sugestao->confidence);
        $this->assertNull($sugestao->toArray()['confidence']);
    }

    // ── Forma do DTO ──────────────────────────────────────────────────────────

    public function test_a_sugestao_tem_a_forma_da_secao_3_4(): void
    {
        $this->conceito('Crochê', descricaoCurada: 'Técnica de tecer.');

        $array = $this->assistente()($this->contexto('Tapete de crochê'))->toArray();

        $this->assertSame([
            'suggested_name',
            'short_description',
            'description',
            'keywords',
            'missing_information',
            'source',
            'confidence',
        ], array_keys($array));
    }

    public function test_campos_propostos_permitem_aplicacao_seletiva(): void
    {
        $this->conceito('Crochê', descricaoCurada: 'Técnica de tecer.');

        $sugestao = $this->assistente()($this->contexto('Tapete de crochê'));

        $this->assertSame(['short_description', 'description'], $sugestao->camposPropostos());
    }
}
