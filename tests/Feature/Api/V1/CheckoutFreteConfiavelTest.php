<?php

namespace Tests\Feature\Api\V1;

use App\Enums\UserRole;
use App\Models\CustomerAddress;
use App\Models\Expositor;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderSplit;
use App\Models\Product;
use App\Models\ProductOffer;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * FIN-SEC-01C.1 — o frete do checkout da API é decidido pelo servidor.
 *
 * O F-13 permitia que o consumidor da API mandasse `shipping_total` e fosse
 * obedecido: `0`, `0.01` e `99999` eram aceitos e viravam o valor cobrado.
 * Estes testes falham se aquilo voltar.
 *
 * A regra: o cliente informa **qual** serviço escolheu; o preço vem de uma
 * recotação feita no servidor, com o endereço dele e os itens que estão de fato
 * no carrinho.
 */
class CheckoutFreteConfiavelTest extends TestCase
{
    use RefreshDatabase;

    private const PRECO_COTADO = 25.0;

    private static int $counter = 0;

    protected function setUp(): void
    {
        parent::setUp();

        SiteSetting::instance()->update([
            'frete_provedor' => 'frenet',
            'frenet_ativo' => true,
            'frenet_token' => 'token-de-teste',
            'comissao_percentual' => 10,
        ]);
    }

    /** Uma cotação previsível, para que o teste fale de autorização e não de logística. */
    private function fingirCotacao(float $preco = self::PRECO_COTADO): void
    {
        Http::fake([
            'api.frenet.com.br/shipping/quote' => Http::response([
                'ShippingSevicesArray' => [[
                    'ServiceCode' => 'PAC-01',
                    'Carrier' => 'Correios',
                    'ServiceDescription' => 'PAC',
                    'ShippingPrice' => $preco,
                    'DeliveryTime' => '5',
                    'Error' => false,
                ]],
            ]),
        ]);
    }

    /** @return array{expositor: Expositor, offer: ProductOffer} */
    private function loja(string $nome = 'Loja'): array
    {
        self::$counter++;

        $lojista = User::factory()->create(['role' => UserRole::Lojista, 'is_active' => true]);

        $expositor = Expositor::create([
            'user_id' => $lojista->id,
            'name' => $nome.' '.self::$counter,
            'slug' => 'frete-loja-'.self::$counter,
            'is_active' => true,
            'zipcode' => '01001000',
        ]);

        $product = Product::factory()->create([
            'expositor_id' => $expositor->id,
            'item_type' => 'produto',
            'name' => 'Item '.self::$counter,
            'slug' => 'frete-item-'.self::$counter,
            'price' => 100,
            'weight' => 0.5, 'height' => 10, 'width' => 15, 'length' => 20,
        ]);

        return [
            'expositor' => $expositor,
            'offer' => $product->offers()->first(),
        ];
    }

    /** @return array{user: User, address: CustomerAddress} */
    private function cliente(): array
    {
        $user = User::factory()->create();

        $address = CustomerAddress::create([
            'user_id' => $user->id,
            'label' => 'Casa', 'cep' => '04567000', 'rua' => 'Rua Teste', 'numero' => '10',
            'bairro' => 'Centro', 'cidade' => 'Sao Paulo', 'estado' => 'SP', 'is_default' => true,
        ]);

        return compact('user', 'address');
    }

    /** @param  array<string, mixed>  $extra */
    private function checkout(CustomerAddress $address, array $extra = []): TestResponse
    {
        return $this->postJson('/api/v1/checkout', array_merge([
            'customer_name' => 'Cliente',
            'customer_whatsapp' => '(11)99999-0000',
            'delivery_type' => 'entrega',
            'customer_address_id' => $address->id,
        ], $extra));
    }

    // ─── O núcleo do F-13 ───────────────────────────────────────────────────

    /**
     * @return array<int, array{0: float}>
     */
    public static function valoresForjados(): array
    {
        return [[0.0], [0.01], [99999.0]];
    }

    #[DataProvider('valoresForjados')]
    public function test_valor_de_frete_enviado_pelo_cliente_nao_vira_o_valor_cobrado(float $forjado): void
    {
        $this->fingirCotacao();
        ['expositor' => $expositor, 'offer' => $offer] = $this->loja();
        ['user' => $cliente, 'address' => $address] = $this->cliente();

        Sanctum::actingAs($cliente);
        $this->postJson('/api/v1/carrinho/itens', ['product_id' => $offer->product_id, 'quantity' => 1]);

        $resposta = $this->checkout($address, [
            'shipping_total' => $forjado,
            'shipping_options' => [['expositor_id' => $expositor->id, 'service_id' => 'PAC-01']],
        ]);

        // Divergir do valor cotado recusa o pedido — o cliente nunca é cobrado
        // num valor diferente do que o servidor calculou.
        $resposta->assertStatus(422)->assertJsonValidationErrors(['shipping_total']);
        $this->assertSame(0, Order::count());
    }

    public function test_sem_informar_shipping_total_o_servidor_cobra_o_valor_cotado(): void
    {
        $this->fingirCotacao();
        ['expositor' => $expositor, 'offer' => $offer] = $this->loja();
        ['user' => $cliente, 'address' => $address] = $this->cliente();

        Sanctum::actingAs($cliente);
        $this->postJson('/api/v1/carrinho/itens', ['product_id' => $offer->product_id, 'quantity' => 1]);

        $this->checkout($address, [
            'shipping_options' => [['expositor_id' => $expositor->id, 'service_id' => 'PAC-01']],
        ])->assertCreated();

        $order = Order::first();
        $this->assertSame('25.00', $order->shipping_total);
        $this->assertSame('125.00', $order->total_amount);
        $this->assertSame('25.00', $order->splits->first()->shipping_amount);
    }

    // ─── Escolha inválida ───────────────────────────────────────────────────

    public function test_servico_inexistente_e_recusado(): void
    {
        $this->fingirCotacao();
        ['expositor' => $expositor, 'offer' => $offer] = $this->loja();
        ['user' => $cliente, 'address' => $address] = $this->cliente();

        Sanctum::actingAs($cliente);
        $this->postJson('/api/v1/carrinho/itens', ['product_id' => $offer->product_id, 'quantity' => 1]);

        $this->checkout($address, [
            'shipping_options' => [['expositor_id' => $expositor->id, 'service_id' => 'SERVICO-FORJADO']],
        ])->assertStatus(422)->assertJsonValidationErrors(['shipping_options']);

        $this->assertSame(0, Order::count());
    }

    public function test_sem_escolher_entrega_o_pedido_e_recusado(): void
    {
        $this->fingirCotacao();
        ['offer' => $offer] = $this->loja();
        ['user' => $cliente, 'address' => $address] = $this->cliente();

        Sanctum::actingAs($cliente);
        $this->postJson('/api/v1/carrinho/itens', ['product_id' => $offer->product_id, 'quantity' => 1]);

        // Payload do app antigo: só o total, sem dizer qual serviço.
        $this->checkout($address, ['shipping_total' => 25])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['shipping_options']);

        $this->assertSame(0, Order::count());
    }

    public function test_loja_de_fora_do_carrinho_nao_influencia_o_frete(): void
    {
        $this->fingirCotacao();
        ['expositor' => $expositor, 'offer' => $offer] = $this->loja('Loja No Carrinho');
        ['expositor' => $alheia] = $this->loja('Loja De Fora');
        ['user' => $cliente, 'address' => $address] = $this->cliente();

        Sanctum::actingAs($cliente);
        $this->postJson('/api/v1/carrinho/itens', ['product_id' => $offer->product_id, 'quantity' => 1]);

        $this->checkout($address, [
            'shipping_options' => [
                ['expositor_id' => $expositor->id, 'service_id' => 'PAC-01'],
                ['expositor_id' => $alheia->id, 'service_id' => 'PAC-01'],
            ],
        ])->assertCreated();

        // A loja de fora não entra na conta: o frete é só o da loja comprada.
        $order = Order::first();
        $this->assertSame('25.00', $order->shipping_total);
        $this->assertCount(1, $order->splits);
    }

    // ─── Multi-loja ─────────────────────────────────────────────────────────

    public function test_duas_lojas_somam_os_fretes_cotados(): void
    {
        $this->fingirCotacao();
        ['expositor' => $lojaA, 'offer' => $ofertaA] = $this->loja('Loja A');
        ['expositor' => $lojaB, 'offer' => $ofertaB] = $this->loja('Loja B');
        ['user' => $cliente, 'address' => $address] = $this->cliente();

        Sanctum::actingAs($cliente);
        $this->postJson('/api/v1/carrinho/itens', ['product_id' => $ofertaA->product_id, 'quantity' => 1]);
        $this->postJson('/api/v1/carrinho/itens', ['product_id' => $ofertaB->product_id, 'quantity' => 1]);

        $this->checkout($address, [
            'shipping_options' => [
                ['expositor_id' => $lojaA->id, 'service_id' => 'PAC-01'],
                ['expositor_id' => $lojaB->id, 'service_id' => 'PAC-01'],
            ],
        ])->assertCreated();

        $order = Order::first();
        $this->assertSame('50.00', $order->shipping_total);
        $this->assertEqualsWithDelta(50.0, (float) $order->splits->sum('shipping_amount'), 0.001);

        foreach ($order->splits as $split) {
            $this->assertSame('25.00', $split->shipping_amount);
        }
    }

    // ─── Cobertura obrigatória dos sellers físicos ──────────────────────────

    public function test_loja_sem_opcao_escolhida_impede_o_pedido_inteiro(): void
    {
        $this->fingirCotacao();
        ['expositor' => $lojaA, 'offer' => $ofertaA] = $this->loja('Loja A');
        ['offer' => $ofertaB] = $this->loja('Loja B');
        ['user' => $cliente, 'address' => $address] = $this->cliente();

        Sanctum::actingAs($cliente);
        $this->postJson('/api/v1/carrinho/itens', ['product_id' => $ofertaA->product_id, 'quantity' => 1]);
        $this->postJson('/api/v1/carrinho/itens', ['product_id' => $ofertaB->product_id, 'quantity' => 1]);

        // Só a loja A foi escolhida: pedido parcialmente cotado não existe.
        $this->checkout($address, [
            'shipping_options' => [['expositor_id' => $lojaA->id, 'service_id' => 'PAC-01']],
        ])->assertStatus(422)->assertJsonValidationErrors(['shipping_options']);

        $this->assertSame(0, Order::count());
        $this->assertSame(0, OrderItem::count());
        $this->assertSame(0, OrderSplit::count());
    }

    public function test_loja_que_entrou_no_carrinho_depois_da_escolha_impede_o_pedido(): void
    {
        $this->fingirCotacao();
        ['expositor' => $lojaA, 'offer' => $ofertaA] = $this->loja('Loja Escolhida');
        ['offer' => $ofertaB] = $this->loja('Loja Nova');
        ['user' => $cliente, 'address' => $address] = $this->cliente();

        Sanctum::actingAs($cliente);
        $this->postJson('/api/v1/carrinho/itens', ['product_id' => $ofertaA->product_id, 'quantity' => 1]);

        // O cliente escolheu o frete só de A e, depois, acrescentou B ao carrinho.
        $this->postJson('/api/v1/carrinho/itens', ['product_id' => $ofertaB->product_id, 'quantity' => 1]);

        $this->checkout($address, [
            'shipping_options' => [['expositor_id' => $lojaA->id, 'service_id' => 'PAC-01']],
        ])->assertStatus(422);

        $this->assertSame(0, Order::count());
    }

    public function test_loja_repetida_no_payload_e_recusada(): void
    {
        // Duas opcoes REAIS na cotacao: se o payload trouxer a loja duas vezes,
        // as duas escolhas sao validas e a ambiguidade e genuina.
        Http::fake([
            'api.frenet.com.br/shipping/quote' => Http::response([
                'ShippingSevicesArray' => [
                    ['ServiceCode' => 'PAC-01', 'Carrier' => 'Correios', 'ServiceDescription' => 'PAC', 'ShippingPrice' => 25.0, 'DeliveryTime' => '5', 'Error' => false],
                    ['ServiceCode' => 'SEDEX-01', 'Carrier' => 'Correios', 'ServiceDescription' => 'SEDEX', 'ShippingPrice' => 45.0, 'DeliveryTime' => '2', 'Error' => false],
                ],
            ]),
        ]);
        ['expositor' => $expositor, 'offer' => $offer] = $this->loja();
        ['user' => $cliente, 'address' => $address] = $this->cliente();

        Sanctum::actingAs($cliente);
        $this->postJson('/api/v1/carrinho/itens', ['product_id' => $offer->product_id, 'quantity' => 1]);

        // Duas escolhas para a mesma loja: ambíguo. Escolher silenciosamente
        // uma delas seria deixar o cliente decidir qual vale.
        $this->checkout($address, [
            'shipping_options' => [
                ['expositor_id' => $expositor->id, 'service_id' => 'PAC-01'],
                ['expositor_id' => $expositor->id, 'service_id' => 'SEDEX-01'],
            ],
        ])->assertStatus(422)->assertJsonValidationErrors(['shipping_options.0.expositor_id']);

        $this->assertSame(0, Order::count());
    }

    // ─── Recotação reflete o carrinho do momento ────────────────────────────

    public function test_a_recotacao_usa_a_quantidade_atual_do_carrinho(): void
    {
        ['expositor' => $expositor, 'offer' => $offer] = $this->loja();
        ['user' => $cliente, 'address' => $address] = $this->cliente();

        Sanctum::actingAs($cliente);
        $this->postJson('/api/v1/carrinho/itens', ['product_id' => $offer->product_id, 'quantity' => 5]);

        $this->fingirCotacao();
        $this->checkout($address, [
            'shipping_options' => [['expositor_id' => $expositor->id, 'service_id' => 'PAC-01']],
        ])->assertCreated();

        // A cotação saiu no momento do checkout, com o carrinho como ele está.
        Http::assertSent(function ($request) {
            $itens = $request['ShippingItemArray'] ?? [];

            return ($itens[0]['Quantity'] ?? null) === 5;
        });
    }

    // ─── Falha do provedor nunca vira frete grátis ──────────────────────────

    /**
     * @return array<string, array{0: \Closure}>
     */
    public static function falhasDoProvedor(): array
    {
        return [
            'timeout' => [fn () => Http::fake(['api.frenet.com.br/shipping/quote' => fn () => throw new ConnectionException('timeout')])],
            'erro 500' => [fn () => Http::fake(['api.frenet.com.br/shipping/quote' => Http::response([], 500)])],
            'resposta vazia' => [fn () => Http::fake(['api.frenet.com.br/shipping/quote' => Http::response(['ShippingSevicesArray' => []])])],
            'formato inesperado' => [fn () => Http::fake(['api.frenet.com.br/shipping/quote' => Http::response(['nada' => true])])],
            'preco ausente' => [fn () => Http::fake(['api.frenet.com.br/shipping/quote' => Http::response([
                'ShippingSevicesArray' => [['ServiceCode' => 'PAC-01', 'Carrier' => 'Correios', 'ServiceDescription' => 'PAC', 'Error' => false]],
            ])])],
            'preco invalido' => [fn () => Http::fake(['api.frenet.com.br/shipping/quote' => Http::response([
                'ShippingSevicesArray' => [['ServiceCode' => 'PAC-01', 'Carrier' => 'Correios', 'ServiceDescription' => 'PAC', 'ShippingPrice' => 'abc', 'Error' => false]],
            ])])],
            'preco negativo' => [fn () => Http::fake(['api.frenet.com.br/shipping/quote' => Http::response([
                'ShippingSevicesArray' => [['ServiceCode' => 'PAC-01', 'Carrier' => 'Correios', 'ServiceDescription' => 'PAC', 'ShippingPrice' => -10, 'Error' => false]],
            ])])],
        ];
    }

    #[DataProvider('falhasDoProvedor')]
    public function test_falha_na_cotacao_fecha_o_checkout_em_vez_de_zerar_o_frete(\Closure $falha): void
    {
        $falha();

        ['expositor' => $expositor, 'offer' => $offer] = $this->loja();
        ['user' => $cliente, 'address' => $address] = $this->cliente();

        Sanctum::actingAs($cliente);
        $this->postJson('/api/v1/carrinho/itens', ['product_id' => $offer->product_id, 'quantity' => 1]);

        $resposta = $this->checkout($address, [
            'shipping_options' => [['expositor_id' => $expositor->id, 'service_id' => 'PAC-01']],
        ]);

        // Fail closed: na dúvida sobre o valor, não se cria pedido nenhum —
        // e jamais se inventa frete zero.
        $this->assertContains($resposta->status(), [422, 500]);
        $this->assertSame(0, Order::count());
    }

    public function test_falha_em_uma_das_lojas_impede_o_pedido_inteiro(): void
    {
        ['expositor' => $lojaA, 'offer' => $ofertaA] = $this->loja('Loja Ok');
        ['expositor' => $lojaB, 'offer' => $ofertaB] = $this->loja('Loja Falha');
        ['user' => $cliente, 'address' => $address] = $this->cliente();

        Sanctum::actingAs($cliente);
        $this->postJson('/api/v1/carrinho/itens', ['product_id' => $ofertaA->product_id, 'quantity' => 1]);
        $this->postJson('/api/v1/carrinho/itens', ['product_id' => $ofertaB->product_id, 'quantity' => 1]);

        // A primeira loja cota; a segunda falha. Não existe pedido parcial.
        $chamadas = 0;
        Http::fake(['api.frenet.com.br/shipping/quote' => function () use (&$chamadas) {
            $chamadas++;

            return $chamadas === 1
                ? Http::response(['ShippingSevicesArray' => [[
                    'ServiceCode' => 'PAC-01', 'Carrier' => 'Correios', 'ServiceDescription' => 'PAC',
                    'ShippingPrice' => 25.0, 'DeliveryTime' => '5', 'Error' => false,
                ]]])
                : Http::response([], 500);
        }]);

        $resposta = $this->checkout($address, [
            'shipping_options' => [
                ['expositor_id' => $lojaA->id, 'service_id' => 'PAC-01'],
                ['expositor_id' => $lojaB->id, 'service_id' => 'PAC-01'],
            ],
        ]);

        $this->assertContains($resposta->status(), [422, 500]);
        $this->assertSame(0, Order::count());
        $this->assertSame(0, OrderSplit::count());
    }

    // ─── Endereço de outro cliente ──────────────────────────────────────────

    public function test_endereco_de_outro_cliente_nao_e_aceito(): void
    {
        $this->fingirCotacao();
        ['expositor' => $expositor, 'offer' => $offer] = $this->loja();
        ['user' => $cliente] = $this->cliente();
        ['address' => $enderecoAlheio] = $this->cliente();

        Sanctum::actingAs($cliente);
        $this->postJson('/api/v1/carrinho/itens', ['product_id' => $offer->product_id, 'quantity' => 1]);

        $this->checkout($enderecoAlheio, [
            'shipping_options' => [['expositor_id' => $expositor->id, 'service_id' => 'PAC-01']],
        ])->assertStatus(422)->assertJsonValidationErrors(['customer_address_id']);

        $this->assertSame(0, Order::count());
    }

    // ─── Retirada ───────────────────────────────────────────────────────────

    public function test_na_retirada_o_cliente_nao_consegue_forcar_frete(): void
    {
        $this->fingirCotacao();
        ['expositor' => $expositor, 'offer' => $offer] = $this->loja();
        ['user' => $cliente, 'address' => $address] = $this->cliente();

        Sanctum::actingAs($cliente);
        $this->postJson('/api/v1/carrinho/itens', ['product_id' => $offer->product_id, 'quantity' => 1]);

        $this->postJson('/api/v1/checkout', [
            'customer_name' => 'Cliente',
            'customer_whatsapp' => '(11)99999-0000',
            'delivery_type' => 'retirada',
            'shipping_total' => 99999,
            'shipping_options' => [['expositor_id' => $expositor->id, 'service_id' => 'PAC-01']],
        ])->assertCreated();

        $order = Order::first();
        $this->assertSame('0.00', $order->shipping_total);
        $this->assertSame('100.00', $order->total_amount);
        $this->assertSame('0.00', $order->splits->first()->shipping_amount);
    }
}
