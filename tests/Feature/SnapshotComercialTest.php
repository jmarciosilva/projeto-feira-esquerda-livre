<?php

namespace Tests\Feature;

use App\Actions\Stock\ReleaseOrderStock;
use App\Enums\UserRole;
use App\Livewire\Checkout;
use App\Models\Expositor;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductOffer;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\CartService;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * FIN-SEC-01C — o pedido continua dizendo a verdade.
 *
 * A FIN-SEC-01B garantiu que o pedido sobreviva à saída do vendedor. Aqui a
 * pergunta é outra: passado um tempo, ele ainda consegue explicar **quais
 * condições comerciais valiam no dia da compra**, sem consultar o catálogo,
 * a regra de comissão ou a tabela de frete de hoje.
 */
class SnapshotComercialTest extends TestCase
{
    use RefreshDatabase;

    private static int $counter = 0;

    /** @return array{expositor: Expositor, offer: ProductOffer} */
    private function loja(string $nome = 'Loja', float $preco = 100): array
    {
        self::$counter++;

        $user = User::factory()->create(['role' => UserRole::Lojista, 'is_active' => true]);

        $expositor = Expositor::create([
            'user_id' => $user->id,
            'name' => $nome.' '.self::$counter,
            'slug' => 'snap-loja-'.self::$counter,
            'is_active' => true,
            'zipcode' => '01001000',
        ]);

        $product = Product::factory()->create([
            'expositor_id' => $expositor->id,
            'item_type' => 'produto',
            'name' => 'Item '.self::$counter,
            'slug' => 'snap-item-'.self::$counter,
            'price' => $preco,
            'weight' => 0.5,
            'height' => 10,
            'width' => 15,
            'length' => 20,
        ]);

        return [
            'expositor' => $expositor,
            'offer' => $product->offers()->where('expositor_id', $expositor->id)->first(),
        ];
    }

    private function pedidoDe(ProductOffer $offer, int $qty = 2, float $frete = 0): Order
    {
        $cliente = User::factory()->create();
        $this->actingAs($cliente);

        app(CartService::class)->add($offer, $qty);

        return app(OrderService::class)->createFromCart([
            'customer_name' => 'Cliente',
            'customer_whatsapp' => '(11)99999-0000',
            'delivery_type' => 'retirada',
            'address_cep' => '01001000',
            'address_rua' => 'Rua', 'address_numero' => '1',
            'address_bairro' => 'Centro', 'address_cidade' => 'Sao Paulo', 'address_estado' => 'SP',
            'shipping_total' => $frete,
            'shipping_por_expositor' => $frete > 0 ? [$offer->expositor_id => $frete] : [],
        ], app(CartService::class));
    }

    // ─── Preço ──────────────────────────────────────────────────────────────

    public function test_alterar_o_preco_da_oferta_nao_reescreve_o_pedido(): void
    {
        ['offer' => $offer] = $this->loja('Loja Preco', 100);
        $order = $this->pedidoDe($offer);

        $offer->update(['price' => 150]);

        $item = $order->items->first()->fresh();
        $this->assertSame('100.00', $item->unit_price);
        $this->assertSame('200.00', $item->total_price);
        $this->assertSame('200.00', $order->fresh()->items_total);
    }

    // ─── Comissão ───────────────────────────────────────────────────────────

    public function test_alterar_a_comissao_da_plataforma_nao_recalcula_o_split(): void
    {
        SiteSetting::instance()->update(['comissao_percentual' => 10]);

        ['offer' => $offer] = $this->loja('Loja Comissao', 100);
        $order = $this->pedidoDe($offer);
        $split = $order->splits->first();

        $this->assertSame('10.00', $split->commission_percent);
        $this->assertSame('20.00', $split->commission_amount);
        $this->assertSame('180.00', $split->net_amount);

        SiteSetting::instance()->update(['comissao_percentual' => 30]);

        $split->refresh();
        $this->assertSame('10.00', $split->commission_percent);
        $this->assertSame('20.00', $split->commission_amount);
        $this->assertSame('180.00', $split->net_amount);
    }

    public function test_o_split_fecha_matematicamente(): void
    {
        SiteSetting::instance()->update(['comissao_percentual' => 12.5]);

        ['offer' => $offer] = $this->loja('Loja Matematica', 80);
        $order = $this->pedidoDe($offer, 3);
        $split = $order->splits->first();

        $bruto = (float) $split->gross_amount;
        $comissao = (float) $split->commission_amount;
        $liquido = (float) $split->net_amount;

        $this->assertSame(240.0, $bruto);
        $this->assertSame(round($bruto * 0.125, 2), $comissao);
        $this->assertEqualsWithDelta($bruto - $comissao, $liquido, 0.001);
        $this->assertGreaterThanOrEqual(0, $liquido);
    }

    // ─── Frete por loja ─────────────────────────────────────────────────────

    public function test_o_frete_cobrado_de_cada_loja_fica_registrado_no_split(): void
    {
        ['offer' => $offer] = $this->loja('Loja Frete', 100);
        $order = $this->pedidoDe($offer, 1, frete: 25);

        $split = $order->splits->first();

        $this->assertSame('25.00', $split->shipping_amount);
        $this->assertSame('25.00', $order->fresh()->shipping_total);
    }

    public function test_a_soma_do_frete_dos_splits_bate_com_o_total_do_pedido(): void
    {
        ['offer' => $offer] = $this->loja('Loja Soma', 100);
        $order = $this->pedidoDe($offer, 1, frete: 32.50);

        $this->assertEqualsWithDelta(
            (float) $order->fresh()->shipping_total,
            (float) $order->splits->sum('shipping_amount'),
            0.001,
        );
    }

    // ─── Origem confiável do frete ──────────────────────────────────────────

    public function test_o_cliente_nao_escolhe_o_preco_do_frete(): void
    {
        ['expositor' => $expositor, 'offer' => $offer] = $this->loja('Loja Segura', 100);
        $cliente = User::factory()->create();
        $this->actingAs($cliente);

        app(CartService::class)->add($offer, 1);

        // O componente é um endpoint próprio: nada impede o cliente de chamar
        // `selectShippingOption` com um preço inventado. O servidor só aceita
        // opção que ele mesmo cotou.
        Livewire::actingAs($cliente)
            ->test(Checkout::class)
            ->call('selectShippingOption', $expositor->id, [
                'service_id' => 'forjado',
                'company' => 'Correios',
                'service_name' => 'PAC',
                'price' => 0.01,
                'delivery_time' => 3,
            ])
            ->assertSet('selected_shipping_options', []);
    }

    public function test_checkout_web_continua_cotando_e_aceitando_a_opcao_real(): void
    {
        SiteSetting::instance()->update([
            'frete_provedor' => 'frenet',
            'frenet_ativo' => true,
            'frenet_token' => 'token-de-teste',
        ]);

        Http::fake([
            'api.frenet.com.br/shipping/quote' => Http::response([
                'ShippingSevicesArray' => [[
                    'ServiceCode' => 'PAC-01',
                    'Carrier' => 'Correios',
                    'ServiceDescription' => 'PAC',
                    'ShippingPrice' => 25.0,
                    'DeliveryTime' => '5',
                    'Error' => false,
                ]],
            ]),
        ]);

        ['expositor' => $expositor, 'offer' => $offer] = $this->loja('Loja Web', 100);
        $cliente = User::factory()->create();
        $this->actingAs($cliente);
        app(CartService::class)->add($offer, 1);

        // A cotação do web passou a vir do mesmo serviço que a API usa; este
        // teste falha se aquela refatoração tiver quebrado a tela.
        $componente = Livewire::actingAs($cliente)
            ->test(Checkout::class)
            ->set('delivery_type', 'entrega')
            ->set('shipping_destination_zipcode', '04567000')
            ->call('calculateShipping');

        $cotacoes = $componente->get('shipping_quotes');
        $this->assertArrayHasKey($expositor->id, $cotacoes);
        $this->assertSame('PAC-01', $cotacoes[$expositor->id][0]['service_id']);

        // E a seleção da opção real continua funcionando, com o preço do servidor.
        $componente->call('selectShippingOption', $expositor->id, ['service_id' => 'PAC-01', 'price' => 0.01]);

        $escolhidas = $componente->get('selected_shipping_options');
        $this->assertSame(25.0, $escolhidas[$expositor->id]['price']);
    }

    public function test_frete_de_duas_lojas_nao_e_duplicado_nem_somado_errado(): void
    {
        ['expositor' => $lojaA, 'offer' => $ofertaA] = $this->loja('Loja A', 100);
        ['expositor' => $lojaB, 'offer' => $ofertaB] = $this->loja('Loja B', 200);

        $cliente = User::factory()->create();
        $this->actingAs($cliente);

        $cart = app(CartService::class);
        $cart->add($ofertaA, 1);
        $cart->add($ofertaB, 1);

        $order = app(OrderService::class)->createFromCart([
            'customer_name' => 'Cliente',
            'customer_whatsapp' => '(11)99999-0000',
            'delivery_type' => 'entrega',
            'address_cep' => '01001000', 'address_rua' => 'Rua', 'address_numero' => '1',
            'address_bairro' => 'Centro', 'address_cidade' => 'Sao Paulo', 'address_estado' => 'SP',
            'shipping_total' => 40,
            'shipping_por_expositor' => [$lojaA->id => 15, $lojaB->id => 25],
        ], $cart);

        $splitA = $order->splits->firstWhere('expositor_id', $lojaA->id);
        $splitB = $order->splits->firstWhere('expositor_id', $lojaB->id);

        $this->assertSame('15.00', $splitA->shipping_amount);
        $this->assertSame('25.00', $splitB->shipping_amount);
        $this->assertSame('40.00', $order->fresh()->shipping_total);
        $this->assertEqualsWithDelta(40.0, (float) $order->splits->sum('shipping_amount'), 0.001);

        // O frete não contamina o cálculo do vendedor: comissão e líquido
        // continuam sendo sobre mercadoria.
        $this->assertSame('100.00', $splitA->gross_amount);
        $this->assertSame('200.00', $splitB->gross_amount);
    }

    public function test_frete_de_loja_que_saiu_do_carrinho_nao_e_cobrado(): void
    {
        ['expositor' => $lojaA, 'offer' => $ofertaA] = $this->loja('Loja Fica', 100);
        ['expositor' => $lojaB, 'offer' => $ofertaB] = $this->loja('Loja Sai', 200);

        $cliente = User::factory()->create();
        $this->actingAs($cliente);

        $componente = Livewire::actingAs($cliente)->test(Checkout::class);

        $cart = app(CartService::class);
        $cart->add($ofertaA, 1);
        $itemDeB = $cart->items()->firstWhere('expositor_id', $lojaB->id);
        $cart->add($ofertaB, 1);
        $itemDeB = app(CartService::class)->items()->firstWhere('expositor_id', $lojaB->id);

        // Frete escolhido para as duas lojas...
        $componente->set('selected_shipping_options', [
            $lojaA->id => ['service_id' => '1', 'company' => 'C', 'service_name' => 'S', 'price' => 15.0, 'delivery_time' => 3, 'currency' => 'BRL', 'error_message' => null],
            $lojaB->id => ['service_id' => '1', 'company' => 'C', 'service_name' => 'S', 'price' => 25.0, 'delivery_time' => 3, 'currency' => 'BRL', 'error_message' => null],
        ]);

        // ...e a loja B sai do carrinho por outro caminho (o drawer, a API),
        // sem passar pelos métodos do checkout que limpam as cotações.
        app(CartService::class)->remove($itemDeB->id);

        $order = app(OrderService::class)->createFromCart([
            'customer_name' => 'Cliente',
            'customer_whatsapp' => '(11)99999-0000',
            'delivery_type' => 'entrega',
            'address_cep' => '01001000', 'address_rua' => 'Rua', 'address_numero' => '1',
            'address_bairro' => 'Centro', 'address_cidade' => 'Sao Paulo', 'address_estado' => 'SP',
            'shipping_total' => 40,
            'shipping_por_expositor' => [$lojaA->id => 15, $lojaB->id => 25],
        ], app(CartService::class));

        // O pedido só tem a loja A, então só o frete dela pode ser cobrado —
        // e a soma dos splits tem de fechar com o total do pedido.
        $this->assertCount(1, $order->splits);
        $this->assertSame('15.00', $order->splits->first()->shipping_amount);
        $this->assertSame('15.00', $order->fresh()->shipping_total);
        $this->assertSame('115.00', $order->fresh()->total_amount);
    }

    // ─── Reconstrução do fato comercial ─────────────────────────────────────

    public function test_o_pedido_explica_a_venda_depois_de_tudo_mudar(): void
    {
        SiteSetting::instance()->update(['comissao_percentual' => 10]);

        ['expositor' => $expositor, 'offer' => $offer] = $this->loja('Loja Original', 100);
        $order = $this->pedidoDe($offer, 2, frete: 15);

        $productId = $offer->product_id;

        // O mundo muda: nome, preço, comissão, oferta e produto.
        $expositor->update(['name' => 'Nome Novo']);
        $offer->update(['price' => 999]);
        SiteSetting::instance()->update(['comissao_percentual' => 45]);

        // Desde a FIN-SEC-01E a oferta só pode sair enquanto ainda deve
        // unidades a um pedido em aberto se a reserva for devolvida antes — é
        // o caminho do cancelamento. O que este teste investiga vem depois: o
        // que sobra no pedido quando o mundo vivo desaparece.
        app(ReleaseOrderStock::class)($order);

        $offer->delete();
        Product::whereKey($productId)->delete();
        $expositor->delete();

        $item = $order->items->first()->fresh();
        $split = $order->splits->first()->fresh();

        // O que foi vendido, por quem, quantas, por quanto...
        $this->assertNotEmpty($item->product_name);
        $this->assertStringStartsWith('Loja Original', (string) $item->expositor_name);
        $this->assertSame(2, $item->quantity);
        $this->assertSame('100.00', $item->unit_price);
        $this->assertSame('200.00', $item->total_price);

        // ...e quanto disso era do vendedor, com qual comissão e qual frete.
        $this->assertSame('200.00', $split->gross_amount);
        $this->assertSame('10.00', $split->commission_percent);
        $this->assertSame('20.00', $split->commission_amount);
        $this->assertSame('180.00', $split->net_amount);
        $this->assertSame('15.00', $split->shipping_amount);

        // Nada disso precisou do catálogo vivo.
        $this->assertNull($item->product_id);
        $this->assertNull($item->product_offer_id);
        $this->assertNull($item->expositor_id);
    }
}
