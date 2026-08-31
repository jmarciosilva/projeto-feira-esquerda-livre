<?php

namespace Tests\Feature;

use App\Actions\Catalog\SaveProductWithOffer;
use App\Models\Expositor;
use App\Models\ProductOffer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * CAT-DOM-02G · G-11 — slug, URL e a escolha de vendedor.
 *
 * ## As duas perguntas que o G-11 junta, e que são diferentes
 *
 * **1. A URL escolhe vendedor implicitamente?** Não, e a razão é estrutural: a
 * única URL comercial é `/loja/{expositor}/{produto}`, que resolve loja **e**
 * item — ou seja, exatamente uma oferta. Não existe rota `/produto/{slug}` que
 * precisasse desempatar entre vendedores. Dois expositores sobre o mesmo item
 * terão URLs distintas por construção, e o slug do produto, compartilhado, é a
 * identidade canônica funcionando (D-02G-7).
 *
 * **2. Dois produtos de nomes iguais colidem?** Colidiam. `products.slug` é
 * `UNIQUE` global e era o único slug do projeto sem desambiguação — `Expositor`,
 * `Post`, `Page` e `Event` todos tinham a sua. É defeito anterior a esta trilha,
 * sem relação com multi-oferta, que a auditoria do G-11 encontrou olhando para
 * colisões de slug.
 */
class SlugMultiOfertaTest extends TestCase
{
    use RefreshDatabase;

    private function expositor(): Expositor
    {
        return Expositor::factory()->create(['user_id' => User::factory()->create()->id]);
    }

    private function salvar(Expositor $expositor, string $nome): ProductOffer
    {
        return app(SaveProductWithOffer::class)([
            'item_type' => 'produto',
            'name' => $nome,
            'slug' => Str::slug($nome),
            'price' => 50,
        ], $expositor);
    }

    // ------------------------------------------------- colisão entre produtos

    public function test_dois_produtos_de_mesmo_nome_recebem_slugs_distintos(): void
    {
        $a = $this->salvar($this->expositor(), 'Camiseta Vermelha');
        $b = $this->salvar($this->expositor(), 'Camiseta Vermelha');

        $this->assertSame('camiseta-vermelha', $a->product->slug);
        $this->assertSame('camiseta-vermelha-1', $b->product->slug);
        $this->assertNotSame($a->product_id, $b->product_id);
    }

    public function test_a_terceira_colisao_continua_desambiguando(): void
    {
        $expositor = $this->expositor();

        $slugs = collect(range(1, 3))
            ->map(fn () => $this->salvar($expositor, 'Caneca Azul')->product->slug);

        $this->assertSame(['caneca-azul', 'caneca-azul-1', 'caneca-azul-2'], $slugs->all());
        $this->assertSame(3, $slugs->unique()->count());
    }

    /**
     * O slug de um item existente **não** é reescrito ao salvar: ele sai do
     * payload de update, e permalinks publicados não mudam sozinhos.
     */
    public function test_editar_o_item_nao_reescreve_o_slug(): void
    {
        $oferta = $this->salvar($this->expositor(), 'Bolsa de Palha');
        $slugOriginal = $oferta->product->slug;

        app(SaveProductWithOffer::class)([
            'item_type' => 'produto',
            'name' => 'Bolsa de Palha Trançada',
            'slug' => 'bolsa-de-palha-trancada',
            'price' => 80,
        ], $oferta->expositor, $oferta->fresh(), $oferta->expositor->user);

        $this->assertSame($slugOriginal, $oferta->product->fresh()->slug);
    }

    // ------------------------------------------------ URL não escolhe vendedor

    /**
     * O ponto do G-11: com duas ofertas sobre o mesmo item, cada loja tem a sua
     * URL, e nenhuma delas resolve para o vendedor errado.
     */
    public function test_cada_loja_tem_url_propria_para_o_mesmo_item(): void
    {
        $ofertaA = $this->salvar($this->expositor(), 'Vaso de Cerâmica');
        $produto = $ofertaA->product;

        $expositorB = $this->expositor();
        $ofertaB = ProductOffer::factory()->create([
            'product_id' => $produto->id,
            'expositor_id' => $expositorB->id,
            'price' => 999,
        ]);

        $urlA = route('loja.produto', [$ofertaA->expositor->slug, $produto->slug]);
        $urlB = route('loja.produto', [$expositorB->slug, $produto->slug]);

        $this->assertNotSame($urlA, $urlB);

        // Cada URL mostra o preço da SUA loja — nenhuma cai na outra.
        $this->get($urlA)->assertOk()->assertSee(number_format((float) $ofertaA->price, 2, ',', '.'));
        $this->get($urlB)->assertOk()->assertSee('999,00');
    }

    /**
     * E a URL de uma loja que não oferece o item não resolve para a loja que
     * oferece: 404, nunca substituição.
     */
    public function test_url_de_loja_sem_oferta_nao_cai_na_loja_que_tem(): void
    {
        $oferta = $this->salvar($this->expositor(), 'Tapete de Juta');
        $semOferta = $this->expositor();

        $this->get(route('loja.produto', [$semOferta->slug, $oferta->product->slug]))
            ->assertNotFound();
    }

    /**
     * Não existe rota canônica `/produto/{slug}` — e é isso que torna a
     * pergunta "qual vendedor esta URL significa?" inexistente por construção.
     */
    public function test_nao_existe_url_comercial_sem_loja(): void
    {
        $rotas = collect(app('router')->getRoutes())
            ->map(fn ($r) => $r->uri())
            ->filter(fn (string $uri) => str_starts_with($uri, 'produto/'));

        $this->assertTrue($rotas->isEmpty(), "Rota comercial sem loja encontrada: {$rotas->implode(', ')}");
    }

    /** A oferta não tem slug próprio, e não precisa ter. */
    public function test_a_oferta_nao_tem_slug(): void
    {
        $this->assertFalse(Schema::hasColumn('product_offers', 'slug'));
    }
}
