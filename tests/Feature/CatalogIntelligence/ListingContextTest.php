<?php

namespace Tests\Feature\CatalogIntelligence;

use App\Actions\Catalog\SaveProductWithOffer;
use App\CatalogIntelligence\Actions\AssociateProductKnowledge;
use App\CatalogIntelligence\Actions\CreateOrUpdateKnowledge;
use App\CatalogIntelligence\Actions\MatchProductKnowledge;
use App\CatalogIntelligence\DTOs\ListingContext;
use App\CatalogIntelligence\DTOs\ProductKnowledgeInput;
use App\CatalogIntelligence\Enums\KnowledgeEntryType;
use App\CatalogIntelligence\Enums\KnowledgeSource;
use App\CatalogIntelligence\Enums\KnowledgeStatus;
use App\CatalogIntelligence\Models\KnowledgeEntry;
use App\CatalogIntelligence\Queries\FindSimilarProducts;
use App\CatalogIntelligence\Support\ContextSanitizer;
use App\Enums\ItemType;
use App\Models\ContentCategory;
use App\Models\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * CAT-05C — o insumo do assistente de conteúdo.
 *
 * O que estes testes protegem não é o formato do DTO: é a fronteira. Tudo o
 * que a CAT-05D vier a ler sai daqui, e a garantia que interessa é a do que
 * **não** entra.
 *
 * Nenhum caso depende de dado real associado no banco — a P-1 (backfill de
 * `catalog_product_knowledge`, hoje com zero linhas) segue pendente, e os
 * cenários são montados por factory e pelas Actions, como a CAT-04 já fazia.
 */
class ListingContextTest extends TestCase
{
    use RefreshDatabase;

    private function conceito(string $nome, KnowledgeEntryType $tipo = KnowledgeEntryType::Technique): KnowledgeEntry
    {
        return app(CreateOrUpdateKnowledge::class)($tipo, $nome, KnowledgeSource::HumanCurated);
    }

    private function candidatosPara(string $texto)
    {
        return app(MatchProductKnowledge::class)(new ProductKnowledgeInput(name: $texto));
    }

    // ── Construção ────────────────────────────────────────────────────────────

    public function test_contexto_de_item_ainda_nao_salvo_funciona_so_com_o_nome(): void
    {
        $contexto = ListingContext::paraItemNovo(ItemType::Produto, 'Tapete de crochê');

        $this->assertSame(ItemType::Produto, $contexto->itemType);
        $this->assertSame('Tapete de crochê', $contexto->name);
        $this->assertNull($contexto->existingShortDescription);
        $this->assertNull($contexto->existingDescription);
        $this->assertSame([], $contexto->categoryPath);
        $this->assertSame([], $contexto->knownAttributes);
        $this->assertSame([], $contexto->knowledge);
        $this->assertSame([], $contexto->similarItems);

        $this->assertSame(0, Product::count(), 'montar contexto não cria item');
    }

    public function test_contexto_a_partir_de_produto_existente_le_a_identidade(): void
    {
        $categoria = ContentCategory::create(['name' => 'Artesanato', 'eixo' => 'produto']);

        $product = Product::factory()->create([
            'item_type' => ItemType::Produto->value,
            'name' => 'Tapete de crochê',
            'short_description' => 'Tapete redondo feito à mão.',
            'description' => 'Peça artesanal em algodão, crochê tradicional.',
            'category_id' => $categoria->id,
        ]);

        $contexto = ListingContext::deProduct($product->load('category'));

        $this->assertSame('Tapete de crochê', $contexto->name);
        $this->assertSame(ItemType::Produto, $contexto->itemType);
        $this->assertSame('Tapete redondo feito à mão.', $contexto->existingShortDescription);
        $this->assertSame('Peça artesanal em algodão, crochê tradicional.', $contexto->existingDescription);
        $this->assertSame(['Artesanato'], $contexto->categoryPath);
    }

    public function test_caminho_da_categoria_inclui_os_ancestrais_do_topo_para_baixo(): void
    {
        $raiz = ContentCategory::create(['name' => 'Casa', 'eixo' => 'produto']);
        $meio = ContentCategory::create(['name' => 'Decoração', 'eixo' => 'produto', 'parent_id' => $raiz->id]);
        $folha = ContentCategory::create(['name' => 'Tapetes', 'eixo' => 'produto', 'parent_id' => $meio->id]);

        $product = Product::factory()->create(['category_id' => $folha->id]);

        $this->assertSame(
            ['Casa', 'Decoração', 'Tapetes'],
            ListingContext::deProduct($product->load('category'))->categoryPath,
        );
    }

    public function test_item_sem_categoria_produz_caminho_vazio(): void
    {
        $product = Product::factory()->create(['category_id' => null]);

        $this->assertSame([], ListingContext::deProduct($product)->categoryPath);
    }

    public function test_texto_em_branco_vira_ausencia(): void
    {
        $contexto = ListingContext::paraItemNovo(
            ItemType::Servico,
            'Consultoria',
            shortDescription: '   ',
            description: '',
        );

        $this->assertNull($contexto->existingShortDescription);
        $this->assertNull($contexto->existingDescription);
        $this->assertContains('short_description', $contexto->lacunas());
        $this->assertContains('description', $contexto->lacunas());
    }

    // ── A fronteira com a oferta (D-CAT-05B-3) ────────────────────────────────

    /**
     * O caso que a CAT-DOM-02 tornou possível: o produto vem com a oferta
     * carregada em memória, e nem assim o contexto a enxerga.
     */
    public function test_contexto_nao_expoe_campo_da_oferta_mesmo_com_a_oferta_carregada(): void
    {
        $product = Product::factory()->comOferta([
            'price' => 199.90,
            'stock_quantity' => 42,
            'weight' => 1.5,
        ])->create(['name' => 'Tapete de crochê']);

        $product->load('offers.expositor');
        $this->assertNotNull($product->offers->first(), 'o cenário exige a oferta carregada');

        $serializado = json_encode(ListingContext::deProduct($product)->toArray());

        foreach (['199.9', '"42"', '42,', 'price', 'stock', 'weight', 'expositor'] as $vestigio) {
            $this->assertStringNotContainsString($vestigio, $serializado, "vazou: {$vestigio}");
        }
    }

    public function test_o_construtor_nao_aceita_oferta_nem_expositor(): void
    {
        $parametros = collect((new \ReflectionClass(ListingContext::class))->getMethods())
            ->filter(fn (\ReflectionMethod $m) => $m->isPublic() || $m->isPrivate())
            ->flatMap(fn (\ReflectionMethod $m) => $m->getParameters())
            ->map(fn (\ReflectionParameter $p) => (string) $p->getType())
            ->implode(' ');

        $this->assertStringNotContainsString('ProductOffer', $parametros);
        $this->assertStringNotContainsString('Expositor', $parametros);
    }

    public function test_contexto_nao_guarda_model_eloquent(): void
    {
        $product = Product::factory()->create(['name' => 'Tapete de crochê']);
        $contexto = ListingContext::deProduct($product);

        foreach ((new \ReflectionClass($contexto))->getProperties() as $propriedade) {
            $valor = $propriedade->getValue($contexto);
            $this->assertFalse(
                $valor instanceof Model,
                "{$propriedade->getName()} guarda um model",
            );
        }
    }

    // ── ContextSanitizer: uma categoria de dado sensível por vez (§5.2) ────────

    public function test_sanitizer_bloqueia_identidade_pessoal(): void
    {
        $limpos = app(ContextSanitizer::class)->atributos([
            'material' => 'algodão',
            'cpf' => '123.456.789-00',
            'cnpj' => '12.345.678/0001-00',
            'user_name' => 'jose',
            'user_id' => 7,
        ]);

        $this->assertSame(['material' => 'algodão'], $limpos);
    }

    public function test_sanitizer_bloqueia_contato(): void
    {
        $limpos = app(ContextSanitizer::class)->atributos([
            'tecnica' => 'crochê',
            'email' => 'lojista@exemplo.com',
            'telefone' => '(11) 90000-0000',
            'whatsapp' => '11900000000',
        ]);

        $this->assertSame(['tecnica' => 'crochê'], $limpos);
    }

    public function test_sanitizer_bloqueia_endereco(): void
    {
        $limpos = app(ContextSanitizer::class)->atributos([
            'cor' => 'azul',
            'endereco' => 'Rua das Flores, 100',
            'cep' => '01234-000',
            'cidade' => 'São Paulo',
        ]);

        $this->assertSame(['cor' => 'azul'], $limpos);
    }

    public function test_sanitizer_bloqueia_rastreamento(): void
    {
        $limpos = app(ContextSanitizer::class)->atributos([
            'acabamento' => 'fosco',
            'ip' => '10.0.0.1',
            'visitor_uuid' => 'abc-123',
            'session_uuid' => 'def-456',
            'cookies' => 'a=b',
        ]);

        $this->assertSame(['acabamento' => 'fosco'], $limpos);
    }

    public function test_sanitizer_bloqueia_dado_de_pedido(): void
    {
        $limpos = app(ContextSanitizer::class)->atributos([
            'origem' => 'Cerrado',
            'order_id' => 99,
            'total' => 250.00,
            'mercado_pago_id' => 'mp-1',
        ]);

        $this->assertSame(['origem' => 'Cerrado'], $limpos);
    }

    public function test_sanitizer_bloqueia_todo_campo_da_oferta(): void
    {
        $sanitizer = app(ContextSanitizer::class);

        foreach (ContextSanitizer::camposDaOferta() as $campo) {
            $this->assertTrue(
                $sanitizer->campoEhProibido($campo),
                "campo de oferta não bloqueado: {$campo}",
            );

            $this->assertSame(
                [],
                $sanitizer->atributos([$campo => 'qualquer valor']),
                "campo de oferta passou pelo filtro: {$campo}",
            );
        }
    }

    /** A lista de campos da oferta vem do domínio, não de uma cópia local. */
    public function test_a_lista_de_campos_da_oferta_vem_da_save_product_with_offer(): void
    {
        $daOferta = ContextSanitizer::camposDaOferta();

        foreach (SaveProductWithOffer::CAMPOS_DA_OFERTA as $campo) {
            $this->assertContains($campo, $daOferta);
        }

        foreach (SaveProductWithOffer::ESPELHOS_COMERCIAIS_LEGADOS as $campo) {
            $this->assertContains($campo, $daOferta);
        }
    }

    public function test_chave_proibida_cai_independente_de_grafia(): void
    {
        $limpos = app(ContextSanitizer::class)->atributos([
            'E-Mail' => 'a@b.com',
            'E_mail' => 'c@d.com',
            'EMAIL' => 'e@f.com',
            'Telefone' => '11900000000',
            'material' => 'barro',
        ]);

        $this->assertSame(['material' => 'barro'], $limpos);
    }

    public function test_valor_nao_escalar_nao_entra_nos_atributos(): void
    {
        $product = Product::factory()->create();

        $limpos = app(ContextSanitizer::class)->atributos([
            'material' => 'algodão',
            'produto' => $product,
            'lista' => ['a', 'b'],
        ]);

        $this->assertSame(['material' => 'algodão'], $limpos);
    }

    // ── knownAttributes: só o que foi informado ───────────────────────────────

    public function test_atributos_contem_apenas_o_que_foi_passado_explicitamente(): void
    {
        $contexto = ListingContext::paraItemNovo(
            ItemType::Produto,
            'Tapete de crochê em algodão feito à mão',
            knownAttributes: ['material' => 'algodão'],
        );

        $this->assertSame(['material' => 'algodão'], $contexto->knownAttributes);
    }

    /**
     * O texto menciona a técnica e o conceito existe na base — e ainda assim
     * `knownAttributes` continua vazio. Deduzir atributo a partir do texto é
     * exatamente o que a CAT-03 proibiu, e é o que a CAT-05E vai converter em
     * pedido de informação em vez de em afirmação.
     */
    public function test_atributo_nunca_e_inferido_do_texto_nem_do_conhecimento(): void
    {
        $this->conceito('Crochê');
        $this->conceito('Algodão', KnowledgeEntryType::Material);

        $contexto = ListingContext::paraItemNovo(
            ItemType::Produto,
            'Tapete de crochê em algodão',
        )->comConhecimento($this->candidatosPara('Tapete de crochê em algodão'));

        $this->assertNotEmpty($contexto->knowledge, 'o cenário exige conceitos encontrados');
        $this->assertSame([], $contexto->knownAttributes);
        $this->assertContains('attributes', $contexto->lacunas());
    }

    // ── Conhecimento ──────────────────────────────────────────────────────────

    /**
     * A forma ganhou `terms` na **CAT-05E** (P-4): as palavras-chave passaram a
     * incluir termo comercial e sinônimo, e o conceito precisa carregá-los.
     * Continua não havendo model aqui — só texto.
     */
    public function test_conhecimento_entra_como_texto_e_nao_como_model(): void
    {
        $this->conceito('Crochê');

        $contexto = ListingContext::paraItemNovo(ItemType::Produto, 'Tapete de crochê')
            ->comConhecimento($this->candidatosPara('Tapete de crochê'));

        $this->assertNotEmpty($contexto->knowledge);
        $this->assertSame(['name', 'type', 'description', 'terms'], array_keys($contexto->knowledge[0]));
        $this->assertSame('Crochê', $contexto->knowledge[0]['name']);
        $this->assertIsArray($contexto->knowledge[0]['terms']);
    }

    public function test_conceito_nao_aprovado_nao_entra_no_contexto(): void
    {
        $entry = $this->conceito('Crochê');
        $candidatos = $this->candidatosPara('Tapete de crochê');
        $this->assertNotEmpty($candidatos, 'o cenário exige um candidato');

        // Rebaixado depois do casamento: é o caminho por onde um conceito não
        // aprovado poderia chegar ao contexto vindo de outro lugar.
        $entry->update(['status' => KnowledgeStatus::Draft]);
        $candidatos->first()->entry->refresh();

        $contexto = ListingContext::paraItemNovo(ItemType::Produto, 'Tapete de crochê')
            ->comConhecimento($candidatos);

        $this->assertSame([], $contexto->knowledge);
    }

    // ── Semelhantes: vigência herdada da CAT-05B ──────────────────────────────

    public function test_semelhantes_reaproveitam_o_find_similar_products(): void
    {
        [$origem, $vizinho] = $this->doisItensSemelhantes();

        $contexto = ListingContext::deProduct($origem)
            ->comSemelhantes(app(FindSimilarProducts::class)($origem));

        $this->assertCount(1, $contexto->similarItems);
        $this->assertSame($vizinho->name, $contexto->similarItems[0]['name']);
        $this->assertNotEmpty($contexto->similarItems[0]['shared_concepts']);
        $this->assertNotEmpty($contexto->similarItems[0]['reasons']);
    }

    public function test_semelhante_sem_oferta_vigente_nao_entra_no_contexto(): void
    {
        [$origem, $vizinho] = $this->doisItensSemelhantes();

        $vizinho->offers()->update(['is_active' => false]);

        $contexto = ListingContext::deProduct($origem)
            ->comSemelhantes(app(FindSimilarProducts::class)($origem));

        $this->assertSame([], $contexto->similarItems);
    }

    public function test_semelhantes_nao_carregam_id_nem_dado_comercial(): void
    {
        [$origem] = $this->doisItensSemelhantes();

        $contexto = ListingContext::deProduct($origem)
            ->comSemelhantes(app(FindSimilarProducts::class)($origem));

        $this->assertSame(
            ['name', 'shared_concepts', 'reasons'],
            array_keys($contexto->similarItems[0]),
        );
    }

    // ── Imutabilidade e ponte com o motor ─────────────────────────────────────

    public function test_completar_o_contexto_devolve_copia_e_nao_muta(): void
    {
        $this->conceito('Crochê');
        $original = ListingContext::paraItemNovo(ItemType::Produto, 'Tapete de crochê');

        $completo = $original->comConhecimento($this->candidatosPara('Tapete de crochê'));

        $this->assertSame([], $original->knowledge);
        $this->assertNotEmpty($completo->knowledge);
        $this->assertNotSame($original, $completo);
    }

    public function test_a_ponte_para_a_busca_usa_o_input_da_cat04(): void
    {
        $categoria = ContentCategory::create(['name' => 'Artesanato', 'eixo' => 'produto']);
        $product = Product::factory()->create([
            'name' => 'Tapete de crochê',
            'description' => 'Peça artesanal.',
            'category_id' => $categoria->id,
        ]);

        $input = ListingContext::deProduct($product->load('category'))->paraBuscaDeConhecimento();

        $this->assertInstanceOf(ProductKnowledgeInput::class, $input);
        $this->assertSame('Tapete de crochê', $input->name);
        $this->assertSame('Artesanato', $input->categoryName);
        $this->assertContains('Peça artesanal.', $input->camposTextuais());
    }

    /** @return array{0: Product, 1: Product} */
    private function doisItensSemelhantes(): array
    {
        $this->conceito('Crochê');

        $origem = Product::factory()->create(['name' => 'Tapete de crochê']);
        $vizinho = Product::factory()->create(['name' => 'Toalha de crochê']);

        $associar = app(AssociateProductKnowledge::class);
        foreach ([$origem, $vizinho] as $p) {
            $associar($p, $this->candidatosPara($p->name));
        }

        return [$origem, $vizinho];
    }
}
