<?php

namespace Tests\Feature;

use App\Models\Expositor;
use App\Models\Product;
use App\Models\ProductOffer;
use App\Models\ProductOfferFaq;
use App\Models\ProductQuestion;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * CAT-DOM-02D — a estrutura de conteúdo por oferta, provada contra o banco.
 *
 * Constraint não se prova com mock: o que interessa aqui é o que o banco
 * recusa, não o que o model promete. Cada caso escreve de verdade e espera o
 * erro de verdade.
 */
class ConteudoPorOfertaSchemaTest extends TestCase
{
    use RefreshDatabase;

    private function oferta(): ProductOffer
    {
        $user = User::factory()->create();
        $expositor = Expositor::factory()->create(['user_id' => $user->id]);
        $produto = Product::factory()->create(['expositor_id' => $expositor->id]);

        return $produto->offers()->sole();
    }

    // ------------------------------------------------- product_offers.images

    public function test_oferta_tem_coluna_images_nullable(): void
    {
        $this->assertTrue(Schema::hasColumn('product_offers', 'images'));

        $oferta = $this->oferta();

        $this->assertNull($oferta->images);
    }

    public function test_images_da_oferta_e_convertida_para_array(): void
    {
        $oferta = $this->oferta();

        $oferta->update(['images' => [['thumb' => 'a/t.webp', 'medium' => 'a/m.webp']]]);

        $this->assertSame(
            [['thumb' => 'a/t.webp', 'medium' => 'a/m.webp']],
            $oferta->fresh()->images,
        );
    }

    /**
     * `image_path` é espelho legado do primeiro thumb (dívida D-1, removida na
     * 02H). A estrutura nova não o herda: importar a dívida seria criar hoje o
     * problema que a 02H vai apagar.
     */
    public function test_oferta_nao_recebe_a_divida_do_image_path(): void
    {
        $this->assertFalse(Schema::hasColumn('product_offers', 'image_path'));
    }

    // ---------------------------------------------------- product_offer_faqs

    public function test_faq_da_oferta_exige_oferta(): void
    {
        $this->expectException(QueryException::class);

        DB::table('product_offer_faqs')->insert([
            'product_offer_id' => null,
            'question' => 'q',
            'answer' => 'a',
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_faq_da_oferta_recusa_oferta_inexistente(): void
    {
        $this->expectException(QueryException::class);

        ProductOfferFaq::create([
            'product_offer_id' => 999999,
            'question' => 'q',
            'answer' => 'a',
            'sort_order' => 0,
        ]);
    }

    /**
     * A FAQ é composição da oferta: some com ela. Diferente da pergunta do
     * cliente, que é conteúdo de terceiro e sobrevive (`SET NULL`).
     */
    public function test_excluir_a_oferta_apaga_a_faq_dela_em_cascata(): void
    {
        $oferta = $this->oferta();

        ProductOfferFaq::create([
            'product_offer_id' => $oferta->id,
            'question' => 'Tem garantia?',
            'answer' => 'Um ano.',
            'sort_order' => 0,
        ]);

        $this->assertDatabaseCount('product_offer_faqs', 1);

        $oferta->delete();

        $this->assertDatabaseCount('product_offer_faqs', 0);
    }

    /**
     * `sort_order` é posição, e duas FAQs não podem ocupar a mesma posição
     * dentro de uma oferta. Em `product_faqs` isso era acidental — vinha do
     * writer atribuir o índice do array, nunca do banco exigir.
     */
    public function test_duas_faqs_nao_ocupam_a_mesma_posicao_na_mesma_oferta(): void
    {
        $oferta = $this->oferta();

        ProductOfferFaq::create([
            'product_offer_id' => $oferta->id,
            'question' => 'A',
            'answer' => 'a',
            'sort_order' => 0,
        ]);

        $this->expectException(QueryException::class);

        ProductOfferFaq::create([
            'product_offer_id' => $oferta->id,
            'question' => 'B',
            'answer' => 'b',
            'sort_order' => 0,
        ]);
    }

    public function test_a_mesma_posicao_e_livre_em_ofertas_diferentes(): void
    {
        $a = $this->oferta();
        $b = $this->oferta();

        foreach ([$a, $b] as $oferta) {
            ProductOfferFaq::create([
                'product_offer_id' => $oferta->id,
                'question' => 'Q',
                'answer' => 'A',
                'sort_order' => 0,
            ]);
        }

        $this->assertDatabaseCount('product_offer_faqs', 2);
    }

    // ------------------------------------- product_questions.product_offer_id

    public function test_pergunta_tem_contexto_de_oferta_nullable(): void
    {
        $this->assertTrue(Schema::hasColumn('product_questions', 'product_offer_id'));

        $oferta = $this->oferta();
        $user = User::factory()->create();

        $pergunta = ProductQuestion::create([
            'product_id' => $oferta->product_id,
            'user_id' => $user->id,
            'question' => 'Serve para presente?',
        ]);

        $this->assertNull($pergunta->product_offer_id);
    }

    /**
     * A pergunta é conteúdo do cliente e tem valor histórico: o expositor sair
     * da Feira não pode apagar o que o cliente perguntou. Mesmo tratamento que
     * a FIN-SEC-01B deu a `order_items`.
     */
    public function test_excluir_a_oferta_preserva_a_pergunta_e_anula_o_contexto(): void
    {
        $oferta = $this->oferta();
        $user = User::factory()->create();

        $pergunta = ProductQuestion::create([
            'product_id' => $oferta->product_id,
            'product_offer_id' => $oferta->id,
            'user_id' => $user->id,
            'question' => 'Chega antes do Natal?',
        ]);

        $oferta->delete();

        $pergunta->refresh();

        $this->assertNull($pergunta->product_offer_id);
        $this->assertSame('Chega antes do Natal?', $pergunta->question);
    }

    /**
     * As duas colunas convivem: `product_id` é o agrupamento canônico e o eixo
     * da Catalog Intelligence, e nenhuma substitui a outra (D-CAT-17).
     */
    public function test_o_product_id_da_pergunta_permanece_obrigatorio(): void
    {
        $oferta = $this->oferta();
        $user = User::factory()->create();

        $pergunta = ProductQuestion::create([
            'product_id' => $oferta->product_id,
            'product_offer_id' => $oferta->id,
            'user_id' => $user->id,
            'question' => 'Tem outra cor?',
        ]);

        $this->assertSame($oferta->product_id, $pergunta->product_id);

        $this->expectException(QueryException::class);

        DB::table('product_questions')->insert([
            'product_id' => null,
            'user_id' => $user->id,
            'question' => 'sem produto',
            'is_visible' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_a_pergunta_alcanca_a_oferta_pela_relacao(): void
    {
        $oferta = $this->oferta();
        $user = User::factory()->create();

        $pergunta = ProductQuestion::create([
            'product_id' => $oferta->product_id,
            'product_offer_id' => $oferta->id,
            'user_id' => $user->id,
            'question' => 'Faz entrega?',
        ]);

        $this->assertTrue($pergunta->productOffer->is($oferta));
        $this->assertTrue($oferta->questions->first()->is($pergunta));
    }
}
