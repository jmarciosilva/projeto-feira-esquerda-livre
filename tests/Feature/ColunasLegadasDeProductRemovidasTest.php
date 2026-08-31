<?php

namespace Tests\Feature;

use App\Actions\Catalog\SaveProductWithOffer;
use App\Models\Expositor;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * CAT-DOM-02H — os doze espelhos comerciais não existem mais em `products`.
 *
 * ## Por que este arquivo existe
 *
 * A CAT-DOM-02C parou a escrita nas colunas espelho; elas ficaram no schema,
 * congeladas. Enquanto existiram, qualquer `->update(['price' => ...])` sobre um
 * `Product` voltaria a povoá-las em silêncio, e o catálogo teria de novo dois
 * lugares dizendo o preço.
 *
 * Removidas, a garantia deixa de depender de vigilância: não há mais coluna a
 * reencontrar. Estes testes existem para que a remoção **não seja desfeita por
 * engano** — uma migration futura que recriasse qualquer uma delas falharia
 * aqui, com o nome da coluna no erro.
 *
 * O que não se prova por schema se prova por comportamento: o cadastro real
 * continua entregando preço e estoque à oferta, e a fase não mexeu na
 * autoridade canônica nem habilitou multi-oferta.
 */
class ColunasLegadasDeProductRemovidasTest extends TestCase
{
    use RefreshDatabase;

    private function expositor(): Expositor
    {
        return Expositor::factory()->create(['user_id' => User::factory()->create()->id]);
    }

    // ------------------------------------------------------------- schema

    public function test_nenhum_dos_doze_espelhos_existe_em_products(): void
    {
        foreach (SaveProductWithOffer::ESPELHOS_COMERCIAIS_LEGADOS as $campo) {
            $this->assertFalse(
                Schema::hasColumn('products', $campo),
                "A coluna legada products.{$campo} voltou a existir.",
            );
        }

        $this->assertCount(12, SaveProductWithOffer::ESPELHOS_COMERCIAIS_LEGADOS);
    }

    /** Os mesmos doze campos continuam existindo na oferta, que é a autoridade. */
    public function test_os_doze_continuam_na_oferta(): void
    {
        foreach (SaveProductWithOffer::ESPELHOS_COMERCIAIS_LEGADOS as $campo) {
            $this->assertTrue(
                Schema::hasColumn('product_offers', $campo),
                "product_offers.{$campo} sumiu — a autoridade comercial foi perdida.",
            );
        }
    }

    /**
     * O que a 02H **não** remove.
     *
     * `is_active` é validade canônica (D-CAT-10) e `expositor_id` é proveniência
     * (D-CAT-11) — nenhum dos dois é espelho comercial, e confundi-los com
     * legado apagaria governança e história.
     */
    public function test_as_colunas_canonicas_permanecem(): void
    {
        foreach ([
            'expositor_id',
            'canonical_delegate_expositor_id',
            'canonical_delegated_at',
            'canonical_delegation_revoked_at',
            'is_active',
            'is_digital',
            'images',
            'image_path',
            'slug',
            'name',
            'description',
            'short_description',
            'item_type',
            'category_id',
        ] as $campo) {
            $this->assertTrue(
                Schema::hasColumn('products', $campo),
                "A coluna canônica products.{$campo} foi removida por engano.",
            );
        }
    }

    // -------------------------------------------------------------- model

    public function test_o_model_nao_declara_os_espelhos(): void
    {
        $product = new Product;

        foreach (SaveProductWithOffer::ESPELHOS_COMERCIAIS_LEGADOS as $campo) {
            $this->assertNotContains($campo, $product->getFillable(), "Product::\$fillable ainda aceita {$campo}.");
            $this->assertArrayNotHasKey($campo, $product->getCasts(), "Product ainda faz cast de {$campo}.");
        }

        // E o que é canônico continua declarado.
        $this->assertContains('is_active', $product->getFillable());
        $this->assertContains('expositor_id', $product->getFillable());
    }

    // -------------------------------------------------------- comportamento

    /**
     * A factory continua aceitando os campos comerciais como açúcar de entrada
     * e entregando-os à oferta — sem eles, dezenas de testes de outras fases
     * criariam ofertas sem preço, e o silêncio seria pior que a falha.
     */
    public function test_a_factory_roteia_o_comercial_para_a_oferta(): void
    {
        $produto = Product::factory()->create([
            'expositor_id' => $this->expositor()->id,
            'price' => 77.70,
            'stock_quantity' => 9,
            'is_featured' => true,
            'sort_order' => 4,
        ]);

        $oferta = $produto->offers()->sole();

        $this->assertSame('77.70', $oferta->price);
        $this->assertSame(9, $oferta->stock_quantity);
        $this->assertTrue($oferta->is_featured);
        $this->assertSame(4, $oferta->sort_order);

        $this->assertArrayNotHasKey('price', $produto->fresh()->getAttributes());
    }

    /** `is_active` não é roteado: no produto ele é canônico. */
    public function test_is_active_continua_no_produto(): void
    {
        $produto = Product::factory()->create([
            'expositor_id' => $this->expositor()->id,
            'is_active' => false,
        ]);

        $this->assertFalse($produto->fresh()->is_active);
    }

    /**
     * A vitrine ordena pela oferta, não por uma coluna de `products` — regra da
     * 02C que só agora fica impossível de burlar.
     */
    public function test_a_vitrine_continua_ordenando_pela_oferta(): void
    {
        $expositor = $this->expositor();

        $ultimo = Product::factory()->create(['expositor_id' => $expositor->id, 'sort_order' => 90]);
        $primeiro = Product::factory()->create(['expositor_id' => $expositor->id, 'sort_order' => 1]);

        $ordem = Product::query()->ordenadoPelaVitrine()->pluck('id')->all();

        $this->assertSame(
            array_search($primeiro->id, $ordem, true) < array_search($ultimo->id, $ordem, true),
            true,
        );
    }
}
