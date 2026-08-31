<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Livewire\Lojista\Produtos\ProdutoForm;
use App\Livewire\ProductQandA;
use App\Models\Expositor;
use App\Models\Product;
use App\Models\ProductFaq;
use App\Models\ProductOfferFaq;
use App\Models\ProductQuestion;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * A fronteira do conteúdo por oferta, provada e não prometida.
 *
 * ## Por que a expectativa deste arquivo inverteu
 *
 * Na **CAT-DOM-02D** ele provava o contrário do que prova agora: que a estrutura
 * nova existia e **ninguém a usava**. Aquela era a garantia da fase — a 02D
 * entregava schema sem consumidor, e um writer migrado por engano teria sido
 * antecipação da fase seguinte.
 *
 * A **CAT-DOM-02E** é justamente a fase que atravessa essa fronteira. Manter as
 * asserções antigas seria exigir que o cutover não tivesse acontecido; apagá-las
 * perderia a prova de que ele aconteceu **inteiro**, e não pela metade. Então
 * elas viraram o seu oposto exato: cada teste que dizia "o legado ainda recebe"
 * agora diz "o legado não recebe mais, e o destino comercial recebe".
 *
 * O que **não** mudou é a outra metade do arquivo: a 02F continua não iniciada,
 * e multi-oferta continua desabilitada.
 */
class FronteiraConteudoPorOfertaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        Storage::fake('public');
    }

    private function lojista(): User
    {
        $user = User::factory()->create(['role' => UserRole::Lojista, 'is_active' => true]);
        $user->assignRole('lojista');

        return $user;
    }

    private function cenario(): array
    {
        $lojista = $this->lojista();
        $expositor = Expositor::factory()->create(['user_id' => $lojista->id]);
        $produto = Product::factory()->create(['expositor_id' => $expositor->id]);

        return [$lojista, $produto, $produto->offers()->sole()];
    }

    // ------------------------------------------------------ cutover realizado

    public function test_o_writer_de_imagem_grava_na_oferta_e_nao_no_produto(): void
    {
        [$lojista, $produto, $oferta] = $this->cenario();

        $canonicasAntes = $produto->images;
        $imagePathAntes = $produto->image_path;

        Livewire::actingAs($lojista)
            ->test(ProdutoForm::class, ['product' => $produto])
            ->set('upload1', UploadedFile::fake()->image('nova.jpg'))
            ->call('save');

        $this->assertNotEmpty($oferta->fresh()->images);

        // `products.images` e `image_path` continuam existindo como imagem
        // canônica, e pararam de receber write-through comercial.
        $this->assertSame($canonicasAntes, $produto->fresh()->images);
        $this->assertSame($imagePathAntes, $produto->fresh()->image_path);
    }

    public function test_o_writer_de_faq_grava_na_oferta_e_nao_na_canonica(): void
    {
        [$lojista, $produto, $oferta] = $this->cenario();

        Livewire::actingAs($lojista)
            ->test(ProdutoForm::class, ['product' => $produto])
            ->call('addFaq')
            ->set('faqs.0.question', 'Qual o prazo?')
            ->set('faqs.0.answer', 'Sete dias.')
            ->call('save');

        $this->assertDatabaseHas('product_offer_faqs', [
            'product_offer_id' => $oferta->id,
            'question' => 'Qual o prazo?',
        ]);

        $this->assertSame(0, ProductFaq::where('product_id', $produto->id)->count());
    }

    /**
     * A FAQ canônica é afirmação do catálogo e sobrevive intocada ao lojista
     * reescrevendo a dele — inclusive quando ele apaga tudo.
     */
    public function test_a_faq_canonica_sobrevive_a_edicao_da_faq_comercial(): void
    {
        [$lojista, $produto, $oferta] = $this->cenario();

        $canonica = ProductFaq::create([
            'product_id' => $produto->id,
            'question' => 'Do que este item é feito?',
            'answer' => 'Algodão, verificado pela curadoria.',
            'sort_order' => 0,
        ]);

        ProductOfferFaq::create([
            'product_offer_id' => $oferta->id,
            'question' => 'Antiga comercial',
            'answer' => 'antiga',
            'sort_order' => 0,
        ]);

        Livewire::actingAs($lojista)
            ->test(ProdutoForm::class, ['product' => $produto])
            ->set('faqs', [['question' => 'Nova comercial', 'answer' => 'nova']])
            ->call('save');

        $canonica->refresh();
        $this->assertSame('Do que este item é feito?', $canonica->question);
        $this->assertSame('Algodão, verificado pela curadoria.', $canonica->answer);
        $this->assertSame(1, ProductFaq::where('product_id', $produto->id)->count());

        $this->assertSame(
            ['Nova comercial'],
            ProductOfferFaq::where('product_offer_id', $oferta->id)->pluck('question')->all(),
        );
    }

    public function test_a_pergunta_do_storefront_registra_a_oferta_da_pagina(): void
    {
        [, $produto, $oferta] = $this->cenario();

        $cliente = User::factory()->create();

        Livewire::actingAs($cliente)
            ->test(ProductQandA::class, ['product' => $produto, 'offer' => $oferta])
            ->set('question', 'Vocês entregam em Salvador?')
            ->call('submit');

        $pergunta = ProductQuestion::sole();

        $this->assertSame($produto->id, $pergunta->product_id);
        $this->assertSame($oferta->id, $pergunta->product_offer_id);
    }

    // ---------------------------------------------- fronteira com a 02F / 02G

    /**
     * A 02D criou a estrutura, a 02E migrou writers e readers — e nenhuma das
     * duas habilitou multi-oferta. O cadastro continua produzindo uma oferta.
     */
    public function test_o_cadastro_continua_produzindo_uma_unica_oferta(): void
    {
        [$lojista, $produto] = $this->cenario();

        Livewire::actingAs($lojista)
            ->test(ProdutoForm::class, ['product' => $produto])
            ->set('name', 'Nome revisado')
            ->call('save');

        $this->assertSame(1, $produto->fresh()->offers()->count());
    }

    /**
     * A 02E direciona conteúdo; **não** implementa governança.
     *
     * `answered_by` continua sendo a pessoa e não a loja (assunto da 02F), e a
     * autorização de resposta continua sendo a que a SEC-02 deixou: nenhuma
     * coluna nova de autoria comercial apareceu nesta fase.
     */
    public function test_a_02f_nao_foi_antecipada(): void
    {
        $this->assertTrue(Schema::hasColumn('product_questions', 'answered_by'));

        foreach (['answered_by_expositor_id', 'shop_id', 'seller_id'] as $coluna) {
            $this->assertFalse(
                Schema::hasColumn('product_questions', $coluna),
                "A coluna {$coluna} pertence à governança da 02F e não deve existir nesta fase.",
            );
        }
    }
}
