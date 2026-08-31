<?php

namespace Tests\Feature\Api\V1;

use App\Models\Expositor;
use App\Models\Product;
use App\Models\ProductOffer;
use App\Models\ProductQuestion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * CAT-DOM-02E — o contexto comercial da pergunta na API pública.
 *
 * `product_offer_id` entrou no contrato como **campo opcional**, e é opcional de
 * propósito: torná-lo obrigatório agora quebraria todo cliente que já chama este
 * endpoint. A compatibilidade é datada — quando a aplicação habilitar
 * multi-oferta, uma fase futura pode reavaliar.
 *
 * O que não é negociável é a inequivocidade. Nenhum caminho aqui escolhe uma
 * oferta por conveniência: ou o cliente diz qual é, ou existe exatamente uma, ou
 * a requisição é recusada.
 *
 * Os cenários com duas ofertas são **defesa estrutural**. Multi-oferta continua
 * desabilitada no cadastro; as ofertas são montadas direto no banco para provar
 * que o código não inventa resposta quando o mundo deixar de ser 1:1.
 */
class PerguntaContextoDaOfertaApiTest extends TestCase
{
    use RefreshDatabase;

    private function expositor(): Expositor
    {
        return Expositor::factory()->create(['user_id' => User::factory()->create()->id]);
    }

    private function produtoComUmaOferta(): Product
    {
        return Product::factory()->create(['expositor_id' => $this->expositor()->id]);
    }

    private function segundaOferta(Product $produto): ProductOffer
    {
        return ProductOffer::factory()->create([
            'product_id' => $produto->id,
            'expositor_id' => $this->expositor()->id,
        ]);
    }

    private function perguntar(Product $produto, array $payload = []): TestResponse
    {
        Sanctum::actingAs(User::factory()->create());

        return $this->postJson(
            route('api.v1.produtos.perguntas.store', $produto),
            $payload + ['question' => 'Vocês entregam em Salvador?'],
        );
    }

    // ------------------------------------------------------- compatibilidade

    public function test_sem_o_campo_novo_a_oferta_unica_resolve(): void
    {
        $produto = $this->produtoComUmaOferta();

        $this->perguntar($produto)->assertCreated();

        $this->assertSame(
            $produto->offers()->sole()->id,
            ProductQuestion::sole()->product_offer_id,
        );
    }

    public function test_com_o_campo_novo_correto_a_pergunta_vai_para_a_oferta_informada(): void
    {
        $produto = $this->produtoComUmaOferta();
        $oferta = $produto->offers()->sole();

        $this->perguntar($produto, ['product_offer_id' => $oferta->id])->assertCreated();

        $pergunta = ProductQuestion::sole();

        $this->assertSame($produto->id, $pergunta->product_id);
        $this->assertSame($oferta->id, $pergunta->product_offer_id);
    }

    // --------------------------------------------------------------- recusas

    /**
     * Oferta de outro produto: recusa, e não correção silenciosa.
     *
     * Trocar por "a oferta certa deste produto" seria decidir pelo cliente qual
     * loja ele quis perguntar.
     */
    public function test_oferta_de_outro_produto_e_recusada(): void
    {
        $p1 = $this->produtoComUmaOferta();
        $p2 = $this->produtoComUmaOferta();

        $this->perguntar($p1, ['product_offer_id' => $p2->offers()->sole()->id])
            ->assertStatus(422)
            ->assertJsonValidationErrors('product_offer_id');

        $this->assertSame(0, ProductQuestion::count());
    }

    public function test_produto_sem_oferta_e_recusado(): void
    {
        $produto = Product::factory()->semOferta()->create();

        $this->perguntar($produto)
            ->assertStatus(422)
            ->assertJsonValidationErrors('product_offer_id');

        $this->assertSame(0, ProductQuestion::count());
    }

    /** Defesa estrutural: com duas ofertas e sem contexto, nunca `first()`. */
    public function test_produto_com_duas_ofertas_e_sem_contexto_e_recusado(): void
    {
        $produto = $this->produtoComUmaOferta();
        $this->segundaOferta($produto);

        $this->perguntar($produto)
            ->assertStatus(422)
            ->assertJsonValidationErrors('product_offer_id');

        $this->assertSame(0, ProductQuestion::count());
    }

    public function test_produto_com_duas_ofertas_aceita_a_oferta_explicita(): void
    {
        $produto = $this->produtoComUmaOferta();
        $ofertaB = $this->segundaOferta($produto);

        $this->perguntar($produto, ['product_offer_id' => $ofertaB->id])->assertCreated();

        $this->assertSame($ofertaB->id, ProductQuestion::sole()->product_offer_id);
    }

    public function test_oferta_inexistente_e_recusada(): void
    {
        $produto = $this->produtoComUmaOferta();

        $this->perguntar($produto, ['product_offer_id' => 999999])
            ->assertStatus(422)
            ->assertJsonValidationErrors('product_offer_id');

        $this->assertSame(0, ProductQuestion::count());
    }

    // ------------------------------------------------------- não-inferência

    /**
     * `ofertaVigente` ordena por preço e devolve a mais barata. É resolução
     * legítima para *exibir* preço e péssima para *endereçar* uma pergunta —
     * mandaria o cliente falar com um vendedor que ele não escolheu. Este teste
     * falha se alguém a reaproveitar aqui por simetria com o resto da API.
     */
    public function test_nunca_resolve_pela_oferta_mais_barata(): void
    {
        $produto = $this->produtoComUmaOferta();
        $produto->offers()->sole()->update(['price' => 500]);

        $barata = $this->segundaOferta($produto);
        $barata->update(['price' => 10]);

        $this->assertNotNull($produto->fresh()->ofertaVigente);

        $this->perguntar($produto)->assertStatus(422);

        $this->assertSame(0, ProductQuestion::count());
    }
}
