<?php

namespace Tests\Feature;

use App\Actions\Catalog\BackfillOfferContent;
use App\Models\Expositor;
use App\Models\Product;
use App\Models\ProductFaq;
use App\Models\ProductOffer;
use App\Models\ProductOfferFaq;
use App\Models\ProductQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * CAT-DOM-02D — o backfill que projeta o legado na estrutura por oferta.
 *
 * Dois modos, e a diferença entre eles é o assunto de metade destes testes:
 * `--inicial` é aditivo e nunca sobrescreve; `--reconciliar` é a execução única
 * pré-cutover, que substitui e apaga o que ficou para trás.
 */
class BackfillConteudoPorOfertaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    private function backfill(): BackfillOfferContent
    {
        return app(BackfillOfferContent::class);
    }

    private function inicial(): array
    {
        return ($this->backfill())(BackfillOfferContent::MODO_INICIAL);
    }

    private function reconciliar(): array
    {
        return ($this->backfill())(BackfillOfferContent::MODO_RECONCILIAR);
    }

    private function expositor(): Expositor
    {
        return Expositor::factory()->create(['user_id' => User::factory()->create()->id]);
    }

    /** Produto com exatamente uma oferta — o caso determinístico. */
    private function produtoComOferta(array $atributos = []): Product
    {
        return Product::factory()->create($atributos + ['expositor_id' => $this->expositor()->id]);
    }

    /** @param  list<string>  $arquivos */
    private function comImagens(Product $produto, array $arquivos): Product
    {
        $entradas = [];

        foreach ($arquivos as $arquivo) {
            Storage::disk('public')->put("products/{$arquivo}", "bytes-de-{$arquivo}");
            $entradas[] = [
                'thumb' => "products/{$arquivo}",
                'medium' => "products/{$arquivo}",
            ];
        }

        $produto->forceFill(['images' => $entradas])->save();

        return $produto->refresh();
    }

    // ------------------------------------------------------ imagens · inicial

    public function test_produto_sem_imagem_nao_popula_a_oferta(): void
    {
        $produto = $this->produtoComOferta();

        $resultado = $this->inicial();

        $this->assertTrue($resultado['sucesso']);
        $this->assertNull($produto->offers()->sole()->images);
        $this->assertSame(0, $resultado['metricas']['imagens_arquivos_copiados']);
    }

    public function test_produto_sem_oferta_nao_e_projetado(): void
    {
        $produto = Product::factory()->semOferta()->create();
        $this->comImagens($produto, ['orfao.webp']);

        $resultado = $this->inicial();

        $this->assertSame(0, $resultado['metricas']['ofertas_elegiveis']);
        $this->assertSame(0, $resultado['metricas']['imagens_arquivos_copiados']);
    }

    public function test_produto_com_mais_de_uma_oferta_nao_e_projetado(): void
    {
        $produto = $this->produtoComOferta();
        $this->comImagens($produto, ['ambiguo.webp']);

        ProductOffer::factory()->create([
            'product_id' => $produto->id,
            'expositor_id' => $this->expositor()->id,
        ]);

        $resultado = $this->inicial();

        $this->assertSame(0, $resultado['metricas']['ofertas_elegiveis']);
        $this->assertNull($produto->offers()->first()->images);
    }

    public function test_destino_vazio_recebe_copia_fisica_da_imagem(): void
    {
        $produto = $this->comImagens($this->produtoComOferta(), ['bolsa.webp']);

        $resultado = $this->inicial();

        $oferta = $produto->offers()->sole()->refresh();

        $this->assertTrue($resultado['sucesso']);
        $this->assertCount(1, $oferta->images);
        $this->assertSame(1, $resultado['metricas']['imagens_ofertas_populadas']);

        foreach (['thumb', 'medium'] as $chave) {
            Storage::disk('public')->assertExists($oferta->images[0][$chave]);
        }
    }

    /**
     * O invariante do §17. `ImageService::delete()` apaga por caminho, sem
     * contar referências: um path compartilhado faria o lojista apagar a imagem
     * do catálogo ao remover a dele — silenciosamente, sem recuperação.
     */
    public function test_produto_e_oferta_nunca_compartilham_arquivo_fisico(): void
    {
        $produto = $this->comImagens($this->produtoComOferta(), ['compartilhada.webp']);

        $this->inicial();

        $oferta = $produto->offers()->sole()->refresh();

        foreach (['thumb', 'medium'] as $chave) {
            $this->assertNotSame($produto->images[0][$chave], $oferta->images[0][$chave]);
        }

        // Desigualdade textual não basta: os bytes precisam existir dos dois
        // lados, e apagar um lado não pode levar o outro junto.
        Storage::disk('public')->assertExists($produto->images[0]['medium']);
        Storage::disk('public')->assertExists($oferta->images[0]['medium']);

        Storage::disk('public')->delete($oferta->images[0]['medium']);
        Storage::disk('public')->assertExists($produto->images[0]['medium']);

        $this->assertSame([], $this->backfill()->pathsCompartilhados());
    }

    public function test_a_segunda_execucao_inicial_preserva_o_destino_e_nao_copia_de_novo(): void
    {
        $produto = $this->comImagens($this->produtoComOferta(), ['ideal.webp']);

        $this->inicial();
        $primeiro = $produto->offers()->sole()->refresh()->images;

        $segundo = $this->inicial();

        $this->assertSame($primeiro, $produto->offers()->sole()->refresh()->images);
        $this->assertSame(0, $segundo['metricas']['imagens_arquivos_copiados']);
        $this->assertSame(1, $segundo['metricas']['imagens_ofertas_preservadas']);
    }

    public function test_arquivo_de_origem_ausente_falha_sem_gravar_path_quebrado(): void
    {
        $produto = $this->comImagens($this->produtoComOferta(), ['some.webp']);
        Storage::disk('public')->delete('products/some.webp');

        $resultado = $this->inicial();

        $this->assertFalse($resultado['sucesso']);
        $this->assertSame(1, $resultado['metricas']['imagens_fontes_ausentes']);
        $this->assertNull($produto->offers()->sole()->refresh()->images);
    }

    /**
     * Falha no meio de um conjunto de várias imagens: nada é persistido, e as
     * cópias já feitas naquela tentativa são removidas. Nunca deixar a oferta
     * apontando para um conjunto parcial como se estivesse íntegro.
     */
    public function test_falha_parcial_nao_deixa_projecao_incompleta_nem_lixo(): void
    {
        $produto = $this->comImagens($this->produtoComOferta(), ['ok.webp', 'quebrada.webp']);
        Storage::disk('public')->delete('products/quebrada.webp');

        $antes = Storage::disk('public')->allFiles();

        $resultado = $this->inicial();

        $this->assertFalse($resultado['sucesso']);
        $this->assertNull($produto->offers()->sole()->refresh()->images);
        $this->assertSame($antes, Storage::disk('public')->allFiles());
    }

    // ------------------------------------------------- imagens · reconciliar

    /**
     * O teste que fecha o R-6: entre a 02D e a 02E o lojista continua
     * escrevendo pelo caminho antigo, e a projeção precisa acompanhar.
     */
    public function test_drift_da_imagem_e_corrigido_pela_reconciliacao(): void
    {
        $produto = $this->comImagens($this->produtoComOferta(), ['a.webp']);

        $this->inicial();
        $projecaoA = $produto->offers()->sole()->refresh()->images;

        // O lojista troca A por B pelo caminho legado, o único ativo.
        $produto = $this->comImagens($produto, ['b.webp']);

        $resultado = $this->reconciliar();

        $oferta = $produto->offers()->sole()->refresh();

        $this->assertTrue($resultado['sucesso']);
        $this->assertSame(1, $resultado['metricas']['imagens_ofertas_substituidas']);

        // A fonte permanece intacta; a projeção passa a ser cópia nova de B.
        $this->assertSame('products/b.webp', $produto->refresh()->images[0]['medium']);
        $this->assertNotSame('products/b.webp', $oferta->images[0]['medium']);
        Storage::disk('public')->assertExists($oferta->images[0]['medium']);
        $this->assertSame('bytes-de-b.webp', Storage::disk('public')->get($oferta->images[0]['medium']));

        // A cópia antiga deixou de ser referenciada e saiu do disco.
        $this->assertNotSame($projecaoA[0]['medium'], $oferta->images[0]['medium']);
        Storage::disk('public')->assertMissing($projecaoA[0]['medium']);

        $this->assertSame([], $this->backfill()->pathsCompartilhados());
    }

    public function test_reconciliar_nao_recopia_quando_a_projecao_ainda_e_fiel(): void
    {
        $produto = $this->comImagens($this->produtoComOferta(), ['estavel.webp']);

        $this->inicial();
        $antes = $produto->offers()->sole()->refresh()->images;

        $resultado = $this->reconciliar();

        $this->assertSame($antes, $produto->offers()->sole()->refresh()->images);
        $this->assertSame(0, $resultado['metricas']['imagens_arquivos_copiados']);
        $this->assertSame(1, $resultado['metricas']['imagens_ofertas_preservadas']);
    }

    // ---------------------------------------------------------- FAQ · inicial

    private function faqs(Product $produto, array $pares): void
    {
        ProductFaq::where('product_id', $produto->id)->delete();

        foreach (array_values($pares) as $i => [$pergunta, $resposta]) {
            ProductFaq::create([
                'product_id' => $produto->id,
                'question' => $pergunta,
                'answer' => $resposta,
                'sort_order' => $i,
            ]);
        }
    }

    /** @return list<string> conjunto ordenado do destino, comparável por conteúdo */
    private function destino(ProductOffer $oferta): array
    {
        return ProductOfferFaq::where('product_offer_id', $oferta->id)
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($f) => "{$f->sort_order}|{$f->question}|{$f->answer}")
            ->all();
    }

    public function test_faq_e_copiada_para_a_oferta_e_a_origem_permanece_intacta(): void
    {
        $produto = $this->produtoComOferta();
        $this->faqs($produto, [['A', 'a'], ['B', 'b']]);

        $this->inicial();

        $this->assertSame(['0|A|a', '1|B|b'], $this->destino($produto->offers()->sole()));

        // A 02D copia e não move: apagar a origem quebraria os readers e
        // writers legados, que esta fase não altera.
        $this->assertSame(2, ProductFaq::where('product_id', $produto->id)->count());
    }

    public function test_faq_de_produto_sem_oferta_unica_fica_nao_resolvida(): void
    {
        $semOferta = Product::factory()->semOferta()->create();
        $this->faqs($semOferta, [['Órfã', 'o']]);

        $duasOfertas = $this->produtoComOferta();
        $this->faqs($duasOfertas, [['Ambígua', 'a']]);
        ProductOffer::factory()->create([
            'product_id' => $duasOfertas->id,
            'expositor_id' => $this->expositor()->id,
        ]);

        $resultado = $this->inicial();

        $this->assertSame(2, $resultado['metricas']['faq_nao_resolvidas']);
        $this->assertSame(0, ProductOfferFaq::count());

        // Não migra, mas também não é apagada nem vira canônica por omissão.
        $this->assertSame(1, ProductFaq::where('product_id', $semOferta->id)->count());
        $this->assertSame(1, ProductFaq::where('product_id', $duasOfertas->id)->count());
    }

    public function test_inicial_nao_sobrescreve_faq_ja_presente_no_destino(): void
    {
        $produto = $this->produtoComOferta();
        $oferta = $produto->offers()->sole();

        ProductOfferFaq::create([
            'product_offer_id' => $oferta->id,
            'question' => 'Já estava aqui',
            'answer' => 'sim',
            'sort_order' => 0,
        ]);

        $this->faqs($produto, [['Nova', 'n']]);

        $resultado = $this->inicial();

        $this->assertSame(['0|Já estava aqui|sim'], $this->destino($oferta));
        $this->assertSame(1, $resultado['metricas']['faq_ofertas_preservadas']);
    }

    public function test_segunda_execucao_inicial_nao_duplica_faq(): void
    {
        $produto = $this->produtoComOferta();
        $this->faqs($produto, [['A', 'a'], ['B', 'b']]);

        $this->inicial();
        $this->inicial();

        $this->assertSame(2, ProductOfferFaq::count());
        $this->assertSame(['0|A|a', '1|B|b'], $this->destino($produto->offers()->sole()));
    }

    // ------------------------------------------------------ FAQ · reconciliar

    public function test_reconciliar_cria_faq_quando_a_origem_nasce(): void
    {
        $produto = $this->produtoComOferta();

        $this->inicial();
        $this->assertSame([], $this->destino($produto->offers()->sole()));

        $this->faqs($produto, [['A', 'a']]);
        $this->reconciliar();

        $this->assertSame(['0|A|a'], $this->destino($produto->offers()->sole()));
    }

    public function test_reconciliar_propaga_edicao_sem_duplicar(): void
    {
        $produto = $this->produtoComOferta();
        $this->faqs($produto, [['A', 'a']]);
        $this->inicial();

        $this->faqs($produto, [['A revisada', 'a nova']]);
        $this->reconciliar();

        $this->assertSame(['0|A revisada|a nova'], $this->destino($produto->offers()->sole()));
        $this->assertSame(1, ProductOfferFaq::count());
    }

    /**
     * O caso que `updateOrCreate` sobre `(product_offer_id, sort_order)` não
     * via: a posição 1 sobreviveria no destino sem que nada mandasse apagá-la.
     */
    public function test_reconciliar_remove_faq_que_saiu_da_origem(): void
    {
        $produto = $this->produtoComOferta();
        $this->faqs($produto, [['A', 'a'], ['B', 'b']]);
        $this->inicial();

        $this->faqs($produto, [['A', 'a']]);
        $this->reconciliar();

        $this->assertSame(['0|A|a'], $this->destino($produto->offers()->sole()));
    }

    public function test_reconciliar_esvazia_o_destino_quando_a_origem_e_limpa(): void
    {
        $produto = $this->produtoComOferta();
        $this->faqs($produto, [['A', 'a'], ['B', 'b']]);
        $this->inicial();

        ProductFaq::where('product_id', $produto->id)->delete();
        $this->reconciliar();

        $this->assertSame([], $this->destino($produto->offers()->sole()));
        $this->assertSame(0, ProductOfferFaq::count());
    }

    /**
     * Reordenar por atualização incremental violaria a `UNIQUE` no meio — o
     * MySQL valida por statement, não no commit. Substituir o conjunto não
     * precisa de deslocamento em duas fases.
     */
    public function test_reconciliar_aplica_reordenacao_exata(): void
    {
        $produto = $this->produtoComOferta();
        $this->faqs($produto, [['A', 'a'], ['B', 'b']]);
        $this->inicial();

        $this->faqs($produto, [['B', 'b'], ['A', 'a']]);
        $this->reconciliar();

        $this->assertSame(['0|B|b', '1|A|a'], $this->destino($produto->offers()->sole()));
    }

    public function test_reconciliar_duas_vezes_produz_o_mesmo_estado(): void
    {
        $produto = $this->produtoComOferta();
        $this->faqs($produto, [['A', 'a'], ['B', 'b'], ['C', 'c']]);
        $this->inicial();

        $this->faqs($produto, [['C', 'c'], ['A', 'a']]);

        $this->reconciliar();
        $primeira = $this->destino($produto->offers()->sole());

        $this->reconciliar();
        $segunda = $this->destino($produto->offers()->sole());

        $this->assertSame($primeira, $segunda);
        $this->assertSame(['0|C|c', '1|A|a'], $segunda);
    }

    /** Paridade por conteúdo, nunca por chave primária. */
    public function test_a_reconciliacao_fecha_a_paridade_origem_destino(): void
    {
        $produto = $this->produtoComOferta();
        $this->faqs($produto, [['A', 'a'], ['B', 'b']]);
        $this->inicial();

        $this->faqs($produto, [['B', 'b']]);

        $this->assertNotSame([], $this->backfill()->divergenciasDeFaq());

        $this->reconciliar();

        $this->assertSame([], $this->backfill()->divergenciasDeFaq());
        $this->assertSame(
            ProductFaq::where('product_id', $produto->id)->count(),
            ProductOfferFaq::where('product_offer_id', $produto->offers()->sole()->id)->count(),
        );
    }

    // ---------------------------------------------------------------- perguntas

    private function pergunta(Product $produto): ProductQuestion
    {
        return ProductQuestion::create([
            'product_id' => $produto->id,
            'user_id' => User::factory()->create()->id,
            'question' => 'Pergunta de teste',
        ]);
    }

    public function test_pergunta_recebe_a_oferta_quando_ha_exatamente_uma(): void
    {
        $produto = $this->produtoComOferta();
        $pergunta = $this->pergunta($produto);

        $resultado = $this->inicial();

        $this->assertSame($produto->offers()->sole()->id, $pergunta->refresh()->product_offer_id);
        $this->assertSame(1, $resultado['metricas']['perguntas_resolvidas']);
    }

    public function test_pergunta_de_produto_sem_oferta_continua_sem_contexto(): void
    {
        $produto = Product::factory()->semOferta()->create();
        $pergunta = $this->pergunta($produto);

        $resultado = $this->inicial();

        $this->assertNull($pergunta->refresh()->product_offer_id);
        $this->assertSame(1, $resultado['metricas']['perguntas_nao_resolvidas']);
    }

    public function test_pergunta_de_produto_com_duas_ofertas_continua_sem_contexto(): void
    {
        $produto = $this->produtoComOferta();
        ProductOffer::factory()->create([
            'product_id' => $produto->id,
            'expositor_id' => $this->expositor()->id,
        ]);

        $pergunta = $this->pergunta($produto);

        $resultado = $this->inicial();

        $this->assertNull($pergunta->refresh()->product_offer_id);
        $this->assertSame(1, $resultado['metricas']['perguntas_nao_resolvidas']);
    }

    public function test_pergunta_ja_resolvida_e_preservada_e_a_segunda_execucao_e_no_op(): void
    {
        $produto = $this->produtoComOferta();
        $pergunta = $this->pergunta($produto);

        $this->inicial();
        $resolvida = $pergunta->refresh()->product_offer_id;

        $segundo = $this->inicial();

        $this->assertSame($resolvida, $pergunta->refresh()->product_offer_id);
        $this->assertSame(0, $segundo['metricas']['perguntas_resolvidas']);
    }

    // -------------------------------------------------------------- simulação

    public function test_simular_mede_sem_escrever(): void
    {
        $produto = $this->comImagens($this->produtoComOferta(), ['seca.webp']);
        $this->faqs($produto, [['A', 'a']]);
        $this->pergunta($produto);

        $antes = Storage::disk('public')->allFiles();

        ($this->backfill())(BackfillOfferContent::MODO_INICIAL, simular: true);

        $this->assertNull($produto->offers()->sole()->refresh()->images);
        $this->assertSame(0, ProductOfferFaq::count());
        $this->assertNull(ProductQuestion::first()->product_offer_id);
        $this->assertSame($antes, Storage::disk('public')->allFiles());
    }
}
