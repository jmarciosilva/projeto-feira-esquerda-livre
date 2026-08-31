<?php

namespace Tests\Feature;

use App\Actions\Catalog\BackfillOfferContent;
use App\Models\Expositor;
use App\Models\Product;
use App\Models\ProductFaq;
use App\Models\ProductOffer;
use App\Models\ProductOfferFaq;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * CAT-DOM-02E — o passo 6 do cutover: `product_faqs` volta a ser só canônica.
 *
 * O risco desta operação é apagar demais. `product_faqs` **não guarda autoria**:
 * nada na linha diz se ela veio de um lojista ou da curadoria. Um
 * `DELETE FROM product_faqs` levaria FAQ canônica legítima junto — e é por isso
 * que a remoção acontece por **prova de correspondência**, linha a linha, e o
 * que não se prova permanece.
 */
class CutoverFaqComercialTest extends TestCase
{
    use RefreshDatabase;

    private function backfill(): BackfillOfferContent
    {
        return app(BackfillOfferContent::class);
    }

    private function expositor(): Expositor
    {
        return Expositor::factory()->create(['user_id' => User::factory()->create()->id]);
    }

    private function produto(): Product
    {
        return Product::factory()->create(['expositor_id' => $this->expositor()->id]);
    }

    private function legada(Product $p, string $q, string $a, int $ordem = 0): ProductFaq
    {
        return ProductFaq::create([
            'product_id' => $p->id,
            'question' => $q,
            'answer' => $a,
            'sort_order' => $ordem,
        ]);
    }

    private function daOferta(ProductOffer $o, string $q, string $a, int $ordem = 0): ProductOfferFaq
    {
        return ProductOfferFaq::create([
            'product_offer_id' => $o->id,
            'question' => $q,
            'answer' => $a,
            'sort_order' => $ordem,
        ]);
    }

    public function test_remove_a_faq_comercial_que_ja_chegou_a_oferta(): void
    {
        $produto = $this->produto();
        $oferta = $produto->offers()->sole();

        $this->legada($produto, 'Qual o prazo?', 'Sete dias.');
        $this->daOferta($oferta, 'Qual o prazo?', 'Sete dias.');

        $resultado = $this->backfill()->limparFaqComercialLegada();

        $this->assertSame(1, $resultado['removidas']);
        $this->assertSame(0, ProductFaq::count());

        // O destino não é tocado pela limpeza.
        $this->assertSame(1, ProductOfferFaq::count());
    }

    /**
     * Sem correspondente no destino, a linha fica: pode ser canônica, e na
     * dúvida preserva-se.
     */
    public function test_faq_sem_correspondente_no_destino_e_preservada(): void
    {
        $produto = $this->produto();
        $oferta = $produto->offers()->sole();

        $this->legada($produto, 'Do que é feito?', 'Algodão, verificado pela curadoria.');
        $this->daOferta($oferta, 'Qual o prazo?', 'Sete dias.');

        $resultado = $this->backfill()->limparFaqComercialLegada();

        $this->assertSame(0, $resultado['removidas']);
        $this->assertSame(1, $resultado['preservadas']);
        $this->assertSame(1, ProductFaq::count());
    }

    /** Mesma pergunta, resposta diferente: não é a mesma FAQ. */
    public function test_correspondencia_parcial_nao_autoriza_remocao(): void
    {
        $produto = $this->produto();
        $oferta = $produto->offers()->sole();

        $this->legada($produto, 'Qual o prazo?', 'Sete dias.');
        $this->daOferta($oferta, 'Qual o prazo?', 'Dez dias.');

        $resultado = $this->backfill()->limparFaqComercialLegada();

        $this->assertSame(0, $resultado['removidas']);
        $this->assertSame(1, ProductFaq::count());
    }

    public function test_produto_sem_oferta_determinística_nunca_e_tocado(): void
    {
        $semOferta = Product::factory()->semOferta()->create();
        $this->legada($semOferta, 'Órfã', 'o');

        $duasOfertas = $this->produto();
        ProductOffer::factory()->create([
            'product_id' => $duasOfertas->id,
            'expositor_id' => $this->expositor()->id,
        ]);
        $this->legada($duasOfertas, 'Ambígua', 'a');

        $resultado = $this->backfill()->limparFaqComercialLegada();

        $this->assertSame(0, $resultado['removidas']);
        $this->assertSame(2, $resultado['preservadas']);
        $this->assertSame(2, ProductFaq::count());
    }

    public function test_simular_nao_apaga_nada(): void
    {
        $produto = $this->produto();
        $oferta = $produto->offers()->sole();

        $this->legada($produto, 'Qual o prazo?', 'Sete dias.');
        $this->daOferta($oferta, 'Qual o prazo?', 'Sete dias.');

        $resultado = $this->backfill()->limparFaqComercialLegada(simular: true);

        $this->assertSame(1, $resultado['removidas']);
        $this->assertSame(1, ProductFaq::count());
    }

    public function test_a_limpeza_e_idempotente(): void
    {
        $produto = $this->produto();
        $oferta = $produto->offers()->sole();

        $this->legada($produto, 'A', 'a');
        $this->daOferta($oferta, 'A', 'a');

        $this->backfill()->limparFaqComercialLegada();
        $segunda = $this->backfill()->limparFaqComercialLegada();

        $this->assertSame(0, $segunda['removidas']);
        $this->assertSame(0, ProductFaq::count());
    }

    /**
     * O comando se recusa a limpar sem paridade fechada: apagar a origem antes
     * de o destino tê-la recebido destruiria conteúdo.
     */
    public function test_o_comando_recusa_limpar_sem_paridade(): void
    {
        $produto = $this->produto();
        $this->legada($produto, 'Só na origem', 'ainda não migrada');

        $this->artisan('catalog:backfill-offer-content --limpar-faq-legada')
            ->assertExitCode(1);

        $this->assertSame(1, ProductFaq::count());
    }

    public function test_o_comando_limpa_quando_a_paridade_fecha(): void
    {
        $produto = $this->produto();
        $oferta = $produto->offers()->sole();

        $this->legada($produto, 'A', 'a');
        $this->daOferta($oferta, 'A', 'a');

        $this->artisan('catalog:backfill-offer-content --limpar-faq-legada')
            ->assertExitCode(0);

        $this->assertSame(0, ProductFaq::count());
        $this->assertSame(1, ProductOfferFaq::count());
    }

    public function test_a_limpeza_nao_se_combina_com_os_modos_de_backfill(): void
    {
        $this->artisan('catalog:backfill-offer-content --limpar-faq-legada --inicial')
            ->assertExitCode(2);
    }
}
