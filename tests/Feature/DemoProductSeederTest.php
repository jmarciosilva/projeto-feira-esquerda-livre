<?php

namespace Tests\Feature;

use App\Actions\Catalog\SaveProductWithOffer;
use App\Enums\PriceType;
use App\Models\Expositor;
use App\Models\Product;
use App\Models\ProductOffer;
use Database\Seeders\DemoProductSeeder;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * O seeder de demonstração sobre o schema posterior à CAT-DOM-02H.
 *
 * ## Por que este arquivo existe
 *
 * A 02H removeu de `products` os doze espelhos comerciais, mas o
 * `DemoProductSeeder` ainda terminava com um `Product::query()->update()`
 * levando `price` e `price_type`. Nenhum teste executava o seeder: a suíte
 * ficou verde e o defeito só apareceu no `migrate --seed` de uma instalação
 * limpa.
 *
 * Por isso o teste roda o seeder de verdade e observa o SQL. Não basta o
 * comando terminar — nenhuma escrita em `products` pode citar campo comercial.
 */
class DemoProductSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_o_comercial_vai_so_para_a_oferta_e_o_produto_continua_valido(): void
    {
        $expositor = Expositor::factory()->create(['eixos' => ['produto']]);

        // Item anterior ao seeder, com outra condição de venda e a oferta
        // recolhida: a mensagem promete que todas as ofertas terminam em
        // R$ 0,01, não só as que o seeder acabou de criar.
        $existente = Product::factory()->create([
            'expositor_id' => $expositor->id,
            'price' => 89.90,
            'price_type' => 'por_hora',
        ]);
        $existente->offers()->update(['is_active' => false]);

        $grammar = DB::connection()->getQueryGrammar();
        $tabela = $grammar->wrapTable('products');
        $escritasEmProducts = [];

        DB::listen(function (QueryExecuted $query) use ($tabela, &$escritasEmProducts) {
            $sql = strtolower($query->sql);

            if (str_starts_with($sql, "update {$tabela}") || str_starts_with($sql, "insert into {$tabela}")) {
                $escritasEmProducts[] = $query->sql;
            }
        });

        $this->artisan('db:seed', ['--class' => DemoProductSeeder::class])
            ->expectsOutputToContain('Todas as ofertas ficaram com preço R$ 0,01.')
            ->assertSuccessful()
            ->run();

        // Sem isto, a varredura abaixo passaria vazia se o listener deixasse de
        // reconhecer a tabela.
        $this->assertNotEmpty($escritasEmProducts, 'Nenhuma escrita em products foi capturada.');

        foreach ($escritasEmProducts as $sql) {
            foreach (SaveProductWithOffer::ESPELHOS_COMERCIAIS_LEGADOS as $campo) {
                $this->assertStringNotContainsString(
                    $grammar->wrap($campo),
                    $sql,
                    "O seeder tentou gravar {$campo} em products: {$sql}",
                );
            }
        }

        // Três itens do eixo `produto` mais o que já existia.
        $ofertas = ProductOffer::all();
        $this->assertCount(4, $ofertas);

        foreach ($ofertas as $oferta) {
            $this->assertSame('0.01', $oferta->price);
            $this->assertSame(PriceType::Fixo, $oferta->price_type);
            $this->assertTrue($oferta->is_active);
        }

        // `is_active` do produto é validade canônica (D-CAT-10), não espelho.
        $produtos = Product::all();
        $this->assertCount(4, $produtos);

        foreach ($produtos as $produto) {
            $this->assertTrue($produto->is_active);
        }
    }
}
