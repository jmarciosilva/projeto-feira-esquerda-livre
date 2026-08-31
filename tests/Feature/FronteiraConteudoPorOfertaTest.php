<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Livewire\Lojista\Produtos\ProdutoForm;
use App\Models\Expositor;
use App\Models\Product;
use App\Models\ProductOfferFaq;
use App\Models\ProductQuestion;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * CAT-DOM-02D — a fronteira com a 02E, provada e não prometida.
 *
 * A 02D entrega estrutura **sem consumidor**: ao fim dela a aplicação continua
 * lendo e escrevendo `products.images` e `product_faqs` exatamente como antes.
 * Isso é o que a torna reversível — e é exatamente o tipo de coisa que se perde
 * sem querer, migrando "só um writer" para a estrutura nova porque era fácil.
 *
 * Estes testes falham se a 02E for antecipada dentro da 02D.
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

    public function test_o_writer_legado_de_imagem_continua_escrevendo_no_produto(): void
    {
        [$lojista, $produto, $oferta] = $this->cenario();

        Livewire::actingAs($lojista)
            ->test(ProdutoForm::class, ['product' => $produto])
            ->set('upload1', UploadedFile::fake()->image('nova.jpg'))
            ->call('save');

        $this->assertNotEmpty($produto->fresh()->images);

        // A estrutura nova existe e permanece vazia: o consumidor é a 02E.
        $this->assertNull($oferta->fresh()->images);
    }

    public function test_o_writer_legado_de_faq_continua_escrevendo_em_product_faqs(): void
    {
        [$lojista, $produto, $oferta] = $this->cenario();

        Livewire::actingAs($lojista)
            ->test(ProdutoForm::class, ['product' => $produto])
            ->call('addFaq')
            ->set('faqs.0.question', 'Qual o prazo?')
            ->set('faqs.0.answer', 'Sete dias.')
            ->call('save');

        $this->assertDatabaseHas('product_faqs', [
            'product_id' => $produto->id,
            'question' => 'Qual o prazo?',
        ]);

        $this->assertSame(0, ProductOfferFaq::where('product_offer_id', $oferta->id)->count());
    }

    /**
     * O writer legado nunca escreve `product_offer_id` — e é exatamente isso
     * que torna `WHERE product_offer_id IS NULL` um filtro de reconciliação
     * seguro: toda linha nula é, por construção, linha ainda não reconciliada.
     */
    public function test_o_writer_legado_de_pergunta_nao_preenche_o_contexto_de_oferta(): void
    {
        [, $produto] = $this->cenario();

        $cliente = User::factory()->create();

        $pergunta = ProductQuestion::create([
            'product_id' => $produto->id,
            'user_id' => $cliente->id,
            'question' => 'Tem em outra cor?',
        ]);

        $this->assertNull($pergunta->fresh()->product_offer_id);
    }

    /**
     * A 02D não cria superfície de multi-oferta. O schema passa a suportá-la
     * estruturalmente; o cadastro continua produzindo exatamente uma.
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
}
