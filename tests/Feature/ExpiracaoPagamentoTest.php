<?php

namespace Tests\Feature;

use App\Actions\Orders\ExpireOrder;
use App\Actions\Stock\ReleaseOrderStock;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Exceptions\TransicaoDePedidoInvalida;
use App\Models\Expositor;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductOffer;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\CartService;
use App\Services\MercadoPagoService;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\FalhaSimulada;
use Tests\TestCase;

/**
 * FIN-SEC-01F-C — o Pix que venceu devolve a peça para a prateleira.
 *
 * Antes desta subfase, um pedido pendente que ninguém pagava retinha estoque
 * indefinidamente: não havia prazo, não havia varredor, e `ReleaseOrderStock`
 * — pronta desde a 01E — não tinha quem a chamasse por vencimento.
 *
 * O prazo é **evidência do gateway**, nunca estimativa. `payment_expires_at`
 * nulo não significa "expirado" nem "expira já": significa que a aplicação não
 * sabe quando aquela intenção venceu, e supor uma idade máxima expiraria vendas
 * que ninguém autorizou expirar.
 */
class ExpiracaoPagamentoTest extends TestCase
{
    use RefreshDatabase;

    private static int $counter = 0;

    private function oferta(): ProductOffer
    {
        self::$counter++;

        $lojista = User::factory()->create(['role' => UserRole::Lojista, 'is_active' => true]);

        $expositor = Expositor::create([
            'user_id' => $lojista->id,
            'name' => 'Loja Expira '.self::$counter,
            'slug' => 'expira-loja-'.self::$counter,
            'is_active' => true,
        ]);

        $product = Product::factory()->create([
            'expositor_id' => $expositor->id,
            'item_type' => 'produto',
            'name' => 'Item Expira '.self::$counter,
            'slug' => 'expira-item-'.self::$counter,
            'price' => 100,
        ]);

        $offer = $product->offers()->first();
        $offer->update(['has_stock' => true, 'stock_quantity' => 10]);

        return $offer->fresh();
    }

    private function pedido(ProductOffer $offer, int $qty = 3, ?string $prazo = null): Order
    {
        $this->actingAs(User::factory()->create());

        app(CartService::class)->add($offer, $qty);

        $order = app(OrderService::class)->createFromCart([
            'customer_name' => 'Cliente',
            'customer_whatsapp' => '(11)99999-0000',
            'delivery_type' => 'retirada',
            'address_cep' => '01001000', 'address_rua' => 'Rua', 'address_numero' => '1',
            'address_bairro' => 'Centro', 'address_cidade' => 'Sao Paulo', 'address_estado' => 'SP',
            'shipping_total' => 0,
        ], app(CartService::class));

        if ($prazo !== null) {
            Order::whereKey($order->getKey())->update(['payment_expires_at' => $prazo]);
        }

        return $order->fresh();
    }

    // ─── A coluna e sua semântica ────────────────────────────────────────────

    public function test_pedido_nasce_sem_prazo(): void
    {
        $order = $this->pedido($this->oferta());

        $this->assertNull($order->payment_expires_at);
    }

    public function test_pedido_sem_prazo_nunca_expira(): void
    {
        // `NULL` é "não sei quando vence", e não "vence agora". É o que protege
        // todo pedido histórico e todo pedido manual, sem gateway.
        $offer = $this->oferta();
        $order = $this->pedido($offer, 3);

        app(ExpireOrder::class)($order);

        $this->assertSame(OrderStatus::AguardandoPagamento, $order->fresh()->status);
        $this->assertSame(3, $offer->fresh()->reserved_quantity);
        $this->assertNull($order->fresh()->stock_released_at);
    }

    public function test_prazo_futuro_nao_expira(): void
    {
        $offer = $this->oferta();
        $order = $this->pedido($offer, 3, now()->addHour()->toDateTimeString());

        app(ExpireOrder::class)($order);

        $this->assertSame(OrderStatus::AguardandoPagamento, $order->fresh()->status);
        $this->assertSame(3, $offer->fresh()->reserved_quantity);
    }

    public function test_prazo_vencido_expira_e_devolve_a_reserva(): void
    {
        $offer = $this->oferta();
        $order = $this->pedido($offer, 3, now()->subMinute()->toDateTimeString());

        $this->assertSame(3, $offer->fresh()->reserved_quantity);

        app(ExpireOrder::class)($order);

        $order->refresh();

        $this->assertSame(OrderStatus::Expirado, $order->status);
        $this->assertNotNull($order->stock_released_at);
        $this->assertSame(0, $offer->fresh()->reserved_quantity);
        $this->assertSame(10, $offer->fresh()->stock_quantity);
        $this->assertSame(10, $offer->fresh()->disponivel());
    }

    public function test_prazo_exatamente_agora_expira(): void
    {
        $offer = $this->oferta();
        $order = $this->pedido($offer, 2, now()->toDateTimeString());

        app(ExpireOrder::class)($order);

        $this->assertSame(OrderStatus::Expirado, $order->fresh()->status);
        $this->assertSame(0, $offer->fresh()->reserved_quantity);
    }

    // ─── Idempotência ────────────────────────────────────────────────────────

    public function test_expirar_duas_vezes_nao_devolve_duas_vezes(): void
    {
        $offer = $this->oferta();
        $order = $this->pedido($offer, 3, now()->subMinute()->toDateTimeString());

        app(ExpireOrder::class)($order);
        $liberadoEm = $order->fresh()->stock_released_at;
        $prazo = $order->fresh()->payment_expires_at;

        app(ExpireOrder::class)($order->fresh());

        $order->refresh();

        $this->assertSame(OrderStatus::Expirado, $order->status);
        $this->assertEquals($liberadoEm, $order->stock_released_at);
        $this->assertEquals($prazo, $order->payment_expires_at);
        $this->assertSame(0, $offer->fresh()->reserved_quantity);
        $this->assertSame(10, $offer->fresh()->stock_quantity);
    }

    // ─── A matriz continua mandando ──────────────────────────────────────────

    #[DataProvider('estadosQueNaoExpiram')]
    public function test_estado_incompativel_nao_expira(OrderStatus $estado): void
    {
        $order = $this->pedido($this->oferta(), 1, now()->subDay()->toDateTimeString());
        Order::whereKey($order->getKey())->update(['status' => $estado->value]);

        $this->expectException(TransicaoDePedidoInvalida::class);

        app(ExpireOrder::class)($order->fresh());
    }

    /** @return array<string, array{0: OrderStatus}> */
    public static function estadosQueNaoExpiram(): array
    {
        return [
            'pago' => [OrderStatus::PagamentoConfirmado],
            'cancelado' => [OrderStatus::Cancelado],
            'estornado' => [OrderStatus::Estornado],
            'concluido' => [OrderStatus::Concluido],
        ];
    }

    public function test_pedido_expirado_nao_confirma_pagamento(): void
    {
        // A matriz da 01F-B recusa `Expirado → PagamentoConfirmado`, e o webhook
        // não ganha exceção. O dinheiro fica no gateway como anomalia a
        // reconciliar na 01F-D — o pedido não ressuscita.
        $offer = $this->oferta();
        $order = $this->pedido($offer, 2, now()->subMinute()->toDateTimeString());

        app(ExpireOrder::class)($order);
        $this->assertSame(OrderStatus::Expirado, $order->fresh()->status);

        $this->pagarNoGateway($order, '1001');

        $order->refresh();

        $this->assertSame(OrderStatus::Expirado, $order->status);
        $this->assertNull($order->paid_at);
        $this->assertNull($order->stock_consumed_at);
        $this->assertSame(0, $offer->fresh()->reserved_quantity);
        $this->assertSame(10, $offer->fresh()->stock_quantity);
        // O evento financeiro continua auditável.
        $this->assertArrayHasKey('payment', $order->payment_payload);
    }

    // ─── Rollback ────────────────────────────────────────────────────────────

    public function test_falha_no_meio_nao_deixa_estado_parcial(): void
    {
        $offer = $this->oferta();
        $order = $this->pedido($offer, 3, now()->subMinute()->toDateTimeString());

        // Ponto de falha construído no teste, não na produção: um listener de
        // query que estoura depois de a liberação já ter acontecido, com o
        // pedido prestes a virar Expirado. A comparação ignora as aspas do
        // identificador porque SQLite e MySQL as escrevem diferente.
        DB::listen(function ($query) {
            $sql = str_replace(['`', '"'], '', $query->sql);

            if (str_starts_with($sql, 'update orders set status')) {
                throw new FalhaSimulada('falha simulada antes do commit');
            }
        });

        try {
            app(ExpireOrder::class)($order);
            $this->fail('A falha simulada deveria ter abortado a transação.');
        } catch (FalhaSimulada) {
            // Esperado. O `catch` é da exceção própria, e não de
            // `RuntimeException`: PHPUnit sinaliza `fail()` com uma exceção que
            // também é `RuntimeException`, e capturá-la esconderia o teste
            // passando por engano.
        }

        $order->refresh();

        $this->assertSame(OrderStatus::AguardandoPagamento, $order->status);
        $this->assertNull($order->stock_released_at);
        $this->assertSame(3, $offer->fresh()->reserved_quantity);
        $this->assertSame(10, $offer->fresh()->stock_quantity);
    }

    // ─── O varredor ──────────────────────────────────────────────────────────

    public function test_varredor_pega_apenas_os_vencidos(): void
    {
        $ofertaVencida = $this->oferta();
        $vencido = $this->pedido($ofertaVencida, 3, now()->subMinute()->toDateTimeString());

        $ofertaFutura = $this->oferta();
        $futuro = $this->pedido($ofertaFutura, 2, now()->addHour()->toDateTimeString());

        $ofertaSemPrazo = $this->oferta();
        $semPrazo = $this->pedido($ofertaSemPrazo, 4);

        $this->artisan('orders:expire-payments')->assertSuccessful();

        $this->assertSame(OrderStatus::Expirado, $vencido->fresh()->status);
        $this->assertSame(0, $ofertaVencida->fresh()->reserved_quantity);

        $this->assertSame(OrderStatus::AguardandoPagamento, $futuro->fresh()->status);
        $this->assertSame(2, $ofertaFutura->fresh()->reserved_quantity);

        $this->assertSame(OrderStatus::AguardandoPagamento, $semPrazo->fresh()->status);
        $this->assertSame(4, $ofertaSemPrazo->fresh()->reserved_quantity);
    }

    public function test_varredor_respeita_o_limite_por_execucao(): void
    {
        $ofertas = [];
        $pedidos = [];

        foreach (range(1, 3) as $i) {
            $offer = $this->oferta();
            $ofertas[] = $offer;
            $pedidos[] = $this->pedido($offer, 1, now()->subMinutes(10)->toDateTimeString());
        }

        $this->artisan('orders:expire-payments', ['--limit' => 2])->assertSuccessful();

        $expirados = collect($pedidos)->filter(fn (Order $o) => $o->fresh()->status === OrderStatus::Expirado);

        $this->assertCount(2, $expirados);

        // O que sobrou é alcançado na execução seguinte — a varredura termina
        // de forma previsível em vez de processar indefinidamente.
        $this->artisan('orders:expire-payments', ['--limit' => 2])->assertSuccessful();

        foreach ($pedidos as $pedido) {
            $this->assertSame(OrderStatus::Expirado, $pedido->fresh()->status);
        }

        foreach ($ofertas as $offer) {
            $this->assertSame(0, $offer->fresh()->reserved_quantity);
        }
    }

    public function test_duas_execucoes_do_varredor_nao_liberam_duas_vezes(): void
    {
        $offer = $this->oferta();
        $order = $this->pedido($offer, 3, now()->subMinute()->toDateTimeString());

        $this->artisan('orders:expire-payments')->assertSuccessful();
        $liberadoEm = $order->fresh()->stock_released_at;

        $this->artisan('orders:expire-payments')->assertSuccessful();

        $this->assertEquals($liberadoEm, $order->fresh()->stock_released_at);
        $this->assertSame(0, $offer->fresh()->reserved_quantity);
        $this->assertSame(10, $offer->fresh()->stock_quantity);
    }

    // ─── Normalização do prazo vindo do gateway ──────────────────────────────

    public function test_gateway_com_prazo_valido_normaliza_para_a_coluna(): void
    {
        $order = $this->pedido($this->oferta(), 1);

        $this->notificarGateway($order, '1101', [
            'status' => 'pending',
            'date_of_expiration' => '2026-08-29T18:30:00.000-03:00',
        ]);

        $order->refresh();

        $this->assertNotNull($order->payment_expires_at);
        // Offset explícito, instante inequívoco: 18:30 em -03:00 é 21:30 UTC.
        $this->assertSame('2026-08-29 21:30:00', $order->payment_expires_at->utc()->toDateTimeString());
    }

    public function test_gateway_sem_o_campo_deixa_o_prazo_nulo(): void
    {
        $order = $this->pedido($this->oferta(), 1);

        $this->notificarGateway($order, '1102', ['status' => 'pending']);

        $this->assertNull($order->fresh()->payment_expires_at);
    }

    public function test_gateway_com_valor_invalido_nao_inventa_prazo(): void
    {
        $order = $this->pedido($this->oferta(), 1);

        $this->notificarGateway($order, '1103', [
            'status' => 'pending',
            'date_of_expiration' => 'nao-e-uma-data',
        ]);

        $this->assertNull($order->fresh()->payment_expires_at);
        $this->assertSame(OrderStatus::AguardandoPagamento, $order->fresh()->status);
    }

    public function test_notificacao_posterior_sem_prazo_nao_apaga_o_prazo(): void
    {
        // Uma segunda notificação sem o campo não pode tornar imortal um Pix
        // que já tinha vencimento conhecido.
        $order = $this->pedido($this->oferta(), 1);

        $this->notificarGateway($order, '1104', [
            'status' => 'pending',
            'date_of_expiration' => '2026-08-29T18:30:00.000-03:00',
        ]);

        $prazo = $order->fresh()->payment_expires_at;
        $this->assertNotNull($prazo);

        $this->notificarGateway($order, '1104', ['status' => 'pending']);

        $this->assertEquals($prazo, $order->fresh()->payment_expires_at);
    }

    public function test_o_dominio_nao_consulta_o_payload_para_decidir_expiracao(): void
    {
        // A autoridade operacional é a coluna. O payload permanece como
        // evidência, e ter o prazo só lá dentro não expira nada.
        $offer = $this->oferta();
        $order = $this->pedido($offer, 2);

        Order::whereKey($order->getKey())->update([
            'payment_payload' => json_encode(['payment' => ['date_of_expiration' => '2020-01-01T00:00:00.000-03:00']]),
        ]);

        app(ExpireOrder::class)($order->fresh());

        $this->assertSame(OrderStatus::AguardandoPagamento, $order->fresh()->status);
        $this->assertSame(2, $offer->fresh()->reserved_quantity);
    }

    // ─── Janela interna de checkout ─────────────────────────────────────────

    public function test_pedido_sem_intencao_nasce_com_janela_interna(): void
    {
        $order = $this->pedido($this->oferta());

        $this->assertNull($order->payment_expires_at);
        $this->assertNotNull($order->checkout_expires_at);
        $this->assertEqualsWithDelta(
            now()->addMinutes((int) config('orders.checkout_reservation_minutes'))->timestamp,
            $order->checkout_expires_at->timestamp,
            5,
        );
    }

    public function test_janela_interna_futura_nao_expira(): void
    {
        $offer = $this->oferta();
        $order = $this->pedido($offer, 3);

        app(ExpireOrder::class)($order);

        $this->assertSame(OrderStatus::AguardandoPagamento, $order->fresh()->status);
        $this->assertSame(3, $offer->fresh()->reserved_quantity);
    }

    public function test_janela_interna_vencida_expira_e_devolve_a_reserva(): void
    {
        $offer = $this->oferta();
        $order = $this->pedido($offer, 3);

        Order::whereKey($order->getKey())->update(['checkout_expires_at' => now()->subMinute()]);

        app(ExpireOrder::class)($order->fresh());

        $order->refresh();

        $this->assertSame(OrderStatus::Expirado, $order->status);
        $this->assertNotNull($order->stock_released_at);
        $this->assertSame(0, $offer->fresh()->reserved_quantity);
        $this->assertSame(10, $offer->fresh()->stock_quantity);
    }

    public function test_pedido_legado_sem_nenhum_prazo_nao_expira(): void
    {
        // Migration aditiva, sem backfill: pedido histórico tem as duas colunas
        // nulas. `NULL` continua sendo "não sei", nunca "venceu".
        $offer = $this->oferta();
        $order = $this->pedido($offer, 2);

        Order::whereKey($order->getKey())->update([
            'payment_expires_at' => null,
            'checkout_expires_at' => null,
        ]);

        app(ExpireOrder::class)($order->fresh());
        $this->artisan('orders:expire-payments')->assertSuccessful();

        $this->assertSame(OrderStatus::AguardandoPagamento, $order->fresh()->status);
        $this->assertSame(2, $offer->fresh()->reserved_quantity);
    }

    // ─── Precedência entre os dois relógios ─────────────────────────────────

    public function test_prazo_do_gateway_prevalece_sobre_a_janela_interna(): void
    {
        // Um Pix com uma hora de validade não pode morrer aos trinta minutos
        // porque a janela interna venceu: depois que a intenção externa nasce,
        // a plataforma não tem autoridade para encerrar antes do gateway.
        $offer = $this->oferta();
        $order = $this->pedido($offer, 3);

        Order::whereKey($order->getKey())->update([
            'checkout_expires_at' => now()->subHour(),
            'payment_expires_at' => now()->addHour(),
        ]);

        app(ExpireOrder::class)($order->fresh());
        $this->artisan('orders:expire-payments')->assertSuccessful();

        $this->assertSame(OrderStatus::AguardandoPagamento, $order->fresh()->status);
        $this->assertSame(3, $offer->fresh()->reserved_quantity);
    }

    public function test_prazo_do_gateway_vencido_expira_mesmo_com_janela_futura(): void
    {
        $offer = $this->oferta();
        $order = $this->pedido($offer, 2);

        Order::whereKey($order->getKey())->update([
            'checkout_expires_at' => now()->addHour(),
            'payment_expires_at' => now()->subMinute(),
        ]);

        app(ExpireOrder::class)($order->fresh());

        $this->assertSame(OrderStatus::Expirado, $order->fresh()->status);
        $this->assertSame(0, $offer->fresh()->reserved_quantity);
    }

    // ─── O abandono que originou esta subfase ───────────────────────────────

    public function test_abandono_do_checkout_web_devolve_a_peca_para_a_prateleira(): void
    {
        // O checkout web não cria preferência: mostra o Payment Brick e espera.
        // Quem fecha a aba nunca gera intenção de pagamento — e, até a 01F-C.2,
        // segurava a peça para sempre.
        $offer = $this->oferta();
        $order = $this->pedido($offer, 3);

        $this->assertSame(3, $offer->fresh()->reserved_quantity);
        $this->assertNull($order->payment_expires_at);

        $this->travel((int) config('orders.checkout_reservation_minutes') + 1)->minutes();

        $this->artisan('orders:expire-payments')->assertSuccessful();

        $order->refresh();

        $this->assertSame(OrderStatus::Expirado, $order->status);
        $this->assertNotNull($order->stock_released_at);
        $this->assertSame(0, $offer->fresh()->reserved_quantity);
        $this->assertSame(10, $offer->fresh()->disponivel());
    }

    public function test_abandono_expirado_nao_inicia_mais_pagamento(): void
    {
        $offer = $this->oferta();
        $order = $this->pedido($offer, 2);

        $this->travel((int) config('orders.checkout_reservation_minutes') + 1)->minutes();
        $this->artisan('orders:expire-payments')->assertSuccessful();

        $this->assertSame(OrderStatus::Expirado, $order->fresh()->status);

        SiteSetting::instance()->update([
            'mercado_pago_ativo' => true,
            'mercado_pago_access_token' => 'TEST_TOKEN',
            'mercado_pago_sandbox' => true,
        ]);
        Http::fake();

        $this->expectException(TransicaoDePedidoInvalida::class);

        app(MercadoPagoService::class)->createPreference($order->fresh());
    }

    public function test_expiracao_por_janela_interna_repetida_nao_libera_duas_vezes(): void
    {
        $offer = $this->oferta();
        $order = $this->pedido($offer, 3);

        Order::whereKey($order->getKey())->update(['checkout_expires_at' => now()->subMinute()]);

        $this->artisan('orders:expire-payments')->assertSuccessful();
        $liberadoEm = $order->fresh()->stock_released_at;

        $this->artisan('orders:expire-payments')->assertSuccessful();

        $this->assertEquals($liberadoEm, $order->fresh()->stock_released_at);
        $this->assertSame(0, $offer->fresh()->reserved_quantity);
        $this->assertSame(10, $offer->fresh()->stock_quantity);
    }

    // ─── A corrida entre criar a intenção e o varredor ──────────────────────

    public function test_preferencia_nao_e_gravada_sobre_pedido_expirado_no_meio(): void
    {
        // A guarda de `createPreference` lê o estado antes da chamada HTTP, e
        // entre uma coisa e outra cabe o varredor. A verificação é refeita sob
        // lock **na escrita** — depois que a rede terminou —, e não durante a
        // viagem.
        $offer = $this->oferta();
        $order = $this->pedido($offer, 2);

        SiteSetting::instance()->update([
            'mercado_pago_ativo' => true,
            'mercado_pago_access_token' => 'TEST_TOKEN',
            'mercado_pago_sandbox' => true,
        ]);

        // O pedido expira **durante** a chamada ao gateway.
        Http::fake(function () use ($order) {
            Order::whereKey($order->getKey())->update([
                'status' => OrderStatus::Expirado->value,
                'stock_released_at' => now(),
            ]);

            return Http::response(['id' => 'PREF-TARDIA', 'init_point' => 'https://mp.test/tardia'], 201);
        });

        try {
            app(MercadoPagoService::class)->createPreference($order->fresh());
            $this->fail('A preferência não deveria ter sido gravada sobre um pedido expirado.');
        } catch (TransicaoDePedidoInvalida) {
            // Esperado.
        }

        $order->refresh();

        $this->assertSame(OrderStatus::Expirado, $order->status);
        $this->assertNull($order->mercado_pago_preference_id);
    }

    // ─── V-10: pedido nunca fica pendente sem intenção de pagamento ─────────

    /** Prepara o gateway e devolve o carrinho pronto para o checkout da API. */
    private function checkoutDaApi(ProductOffer $offer, int $qty, int $statusDoGateway): TestResponse
    {
        SiteSetting::instance()->update([
            'mercado_pago_ativo' => true,
            'mercado_pago_access_token' => 'TEST_TOKEN',
            'mercado_pago_sandbox' => true,
        ]);

        Http::fake([
            'api.mercadopago.com/checkout/preferences' => $statusDoGateway === 201
                ? Http::response(['id' => 'PREF-1', 'init_point' => 'https://mp.test/pref-1'], 201)
                : Http::response(['erro' => 'indisponivel'], $statusDoGateway),
        ]);

        Sanctum::actingAs(User::factory()->create());
        app(CartService::class)->add($offer, $qty);

        return $this->postJson('/api/v1/checkout', [
            'customer_name' => 'Cliente',
            'customer_whatsapp' => '(11)99999-0000',
            'delivery_type' => 'retirada',
        ]);
    }

    public function test_falha_ao_criar_a_preferencia_cancela_e_devolve_a_reserva(): void
    {
        // A criação do pedido é transacional; a chamada ao gateway não pode
        // ser, sob pena de manter locks de estoque abertos durante a rede.
        // A saída é compensar: se nenhuma intenção pagável nasceu, o pedido não
        // continua de pé segurando estoque.
        $offer = $this->oferta();

        $this->checkoutDaApi($offer, 3, 500)->assertStatus(502);

        $order = Order::latest('id')->first();

        $this->assertSame(OrderStatus::Cancelado, $order->status);
        $this->assertNotNull($order->stock_released_at);
        $this->assertSame(0, $offer->fresh()->reserved_quantity);
        $this->assertSame(10, $offer->fresh()->stock_quantity);
        $this->assertNull($order->payment_expires_at);
        $this->assertNull($order->mercado_pago_preference_id);
    }

    public function test_falha_do_gateway_cancela_e_nunca_expira(): void
    {
        // A distinção da 01F-B vale aqui: nada expirou, porque nenhuma intenção
        // de pagamento chegou a existir. `Expirado` é o relógio agindo sobre
        // algo que foi válido.
        $offer = $this->oferta();

        $this->checkoutDaApi($offer, 2, 500)->assertStatus(502);

        $this->assertSame(OrderStatus::Cancelado, Order::latest('id')->first()->status);
        $this->assertSame(0, Order::where('status', OrderStatus::Expirado->value)->count());
    }

    public function test_falha_repetida_nao_libera_duas_vezes(): void
    {
        $offer = $this->oferta();

        $this->checkoutDaApi($offer, 3, 500)->assertStatus(502);
        $primeiro = Order::latest('id')->first();
        $liberadoEm = $primeiro->stock_released_at;

        // Um segundo checkout, também falho, é outro pedido — e a compensação
        // de cada um mexe só no seu.
        $this->checkoutDaApi($offer, 2, 500)->assertStatus(502);

        $this->assertEquals($liberadoEm, $primeiro->fresh()->stock_released_at);
        $this->assertSame(0, $offer->fresh()->reserved_quantity);
        $this->assertSame(10, $offer->fresh()->stock_quantity);
        $this->assertSame(2, Order::where('status', OrderStatus::Cancelado->value)->count());
    }

    public function test_sucesso_do_gateway_mantem_o_pedido_e_a_reserva(): void
    {
        $offer = $this->oferta();

        $this->checkoutDaApi($offer, 3, 201)->assertStatus(201);

        $order = Order::latest('id')->first();

        $this->assertSame(OrderStatus::AguardandoPagamento, $order->status);
        $this->assertSame('PREF-1', $order->mercado_pago_preference_id);
        $this->assertNull($order->stock_released_at);
        $this->assertSame(3, $offer->fresh()->reserved_quantity);
    }

    public function test_pedido_encerrado_nao_ganha_nova_intencao_de_pagamento(): void
    {
        // Os caminhos de repetição (`/pedidos/{ref}/pagar`) existem e são
        // idempotentes por `mercado_pago_preference_id`. Sem esta guarda, eles
        // criariam uma tela de pagamento para um pedido que o domínio já
        // encerrou.
        $offer = $this->oferta();
        $order = $this->pedido($offer, 2, now()->subMinute()->toDateTimeString());

        app(ExpireOrder::class)($order);

        SiteSetting::instance()->update([
            'mercado_pago_ativo' => true,
            'mercado_pago_access_token' => 'TEST_TOKEN',
            'mercado_pago_sandbox' => true,
        ]);
        Http::fake();

        $this->expectException(TransicaoDePedidoInvalida::class);

        app(MercadoPagoService::class)->createPreference($order->fresh());
    }

    // ─── Parsing: o contrato do prazo é declarado, não adivinhado ───────────

    #[DataProvider('prazosRecusados')]
    public function test_prazo_fora_do_contrato_nao_vira_timestamp(string $bruto): void
    {
        // `Carbon::parse()` aceitaria `"tomorrow"` e devolveria um instante
        // plausível. Um campo corrompido viraria prazo real e expiraria um
        // pedido legítimo.
        $order = $this->pedido($this->oferta(), 1);

        $this->notificarGateway($order, '1200', [
            'status' => 'pending',
            'date_of_expiration' => $bruto,
        ]);

        $this->assertNull($order->fresh()->payment_expires_at);
    }

    /** @return array<string, array{0: string}> */
    public static function prazosRecusados(): array
    {
        return [
            'expressao humana' => ['tomorrow'],
            'intervalo relativo' => ['+1 day'],
            'so a data' => ['2026-08-29'],
            'sem offset' => ['2026-08-29T18:30:00'],
            'texto livre' => ['nao-e-uma-data'],
        ];
    }

    #[DataProvider('prazosAceitos')]
    public function test_formatos_iso_legitimos_sao_aceitos(string $bruto, string $esperadoUtc): void
    {
        $order = $this->pedido($this->oferta(), 1);

        $this->notificarGateway($order, '1201', [
            'status' => 'pending',
            'date_of_expiration' => $bruto,
        ]);

        $this->assertSame($esperadoUtc, $order->fresh()->payment_expires_at->utc()->toDateTimeString());
    }

    /** @return array<string, array{0: string, 1: string}> */
    public static function prazosAceitos(): array
    {
        return [
            'offset com dois pontos' => ['2026-08-29T18:30:00.000-03:00', '2026-08-29 21:30:00'],
            'offset sem dois pontos' => ['2026-08-29T18:30:00-0300', '2026-08-29 21:30:00'],
            'zulu' => ['2026-08-29T21:30:00Z', '2026-08-29 21:30:00'],
        ];
    }

    /** @param array<string, mixed> $extras */
    private function notificarGateway(Order $order, string $paymentId, array $extras): void
    {
        SiteSetting::instance()->update([
            'mercado_pago_ativo' => true,
            'mercado_pago_access_token' => 'TEST_TOKEN',
            'mercado_pago_sandbox' => true,
        ]);

        Http::fake([
            'api.mercadopago.com/v1/payments/'.$paymentId => Http::response($extras + [
                'id' => (int) $paymentId,
                'external_reference' => $order->reference,
                'transaction_amount' => (float) $order->total_amount,
            ]),
        ]);

        app(MercadoPagoService::class)->syncPayment($paymentId);
    }

    private function pagarNoGateway(Order $order, string $paymentId): void
    {
        $this->notificarGateway($order, $paymentId, [
            'status' => 'approved',
            'date_approved' => '2026-08-29T12:00:00.000-03:00',
        ]);
    }
}
