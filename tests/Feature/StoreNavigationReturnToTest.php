<?php

namespace Tests\Feature;

use App\Models\Expositor;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreNavigationReturnToTest extends TestCase
{
    use RefreshDatabase;

    public function test_back_to_feira_link_returns_to_catalog_after_browsing_store(): void
    {
        $expositor = Expositor::create([
            'name' => 'Ceramica Viva',
            'slug' => 'ceramica-viva',
            'is_active' => true,
        ]);

        $product = Product::factory()->create([
            'expositor_id' => $expositor->id,
            'item_type' => 'produto',
            'name' => 'Vaso de Ceramica',
            'slug' => 'vaso-de-ceramica',
            'price' => 50,
            'is_active' => true,
        ]);

        // 1) Catalogo /produtos deve linkar pro produto levando a URL do catalogo como return_to.
        $catalogUrl = url('/produtos');
        $catalogPage = $this->get('/produtos');
        $catalogPage->assertOk();
        $catalogPage->assertSee(
            route('loja.produto', [$expositor->slug, $product->slug, 'return_to' => $catalogUrl]),
            false
        );

        // 2) Pagina do produto, chegando com return_to do catalogo, deve propagar o mesmo
        // return_to no link de volta para a loja (nao aponta so pra loja sem contexto).
        $productPage = $this->get(route('loja.produto', [
            $expositor->slug, $product->slug, 'return_to' => $catalogUrl,
        ]));
        $productPage->assertOk();
        $productPage->assertSee(
            route('loja.show', [$expositor->slug, 'return_to' => $catalogUrl]),
            false
        );

        // 3) Pagina da loja, chegando com esse mesmo return_to, deve usar o catalogo no
        // link "Feira Esquerda Livre" - nao pode cair na home so porque o referer e /loja/...
        $storePage = $this->get(route('loja.show', [
            $expositor->slug, 'return_to' => $catalogUrl,
        ]));
        $storePage->assertOk();
        $storePage->assertSee('href="'.$catalogUrl.'"', false);
    }
}
