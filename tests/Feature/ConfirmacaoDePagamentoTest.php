<?php

namespace Tests\Feature;

use App\Actions\Payments\ConfirmOrderPayment;
use App\DTO\PaymentConfirmation;
use App\Enums\OrderSplitStatus;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Events\OrderSplitConfirmed;
use App\Models\Ava\AvaCourse;
use App\Models\Ava\AvaEnrollment;
use App\Models\Expositor;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderSplit;
use App\Models\Product;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\MercadoPagoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * FIN-SEC-01D — confirmar um pagamento é uma transição de domínio.
 *
 * Não é um conjunto de updates independentes: ou o pedido inteiro passa a pago,
 * com os splits confirmados e os efeitos executados, ou nada acontece. E
 * receber a mesma confirmação duas vezes tem de produzir exatamente o mesmo
 * estado que recebê-la uma vez.
 */
class ConfirmacaoDePagamentoTest extends TestCase
{
    use RefreshDatabase;

    private static int $counter = 0;

    protected function setUp(): void
    {
        parent::setUp();

        SiteSetting::instance()->update([
            'mercado_pago_ativo' => true,
            'mercado_pago_access_token' => 'TEST_TOKEN',
            'mercado_pago_sandbox' => true,
        ]);
    }

    private function expositor(string $nome = 'Loja'): Expositor
    {
        self::$counter++;

        $lojista = User::factory()->create(['role' => UserRole::Lojista, 'is_active' => true]);

        return Expositor::create([
            'user_id' => $lojista->id,
            'name' => $nome.' '.self::$counter,
            'slug' => 'pag-loja-'.self::$counter,
            'is_active' => true,
        ]);
    }

    /**
     * Um pedido pronto para pagar, com uma loja por valor informado.
     *
     * @param  array<int, float>  $valoresPorLoja
     */
    private function pedido(array $valoresPorLoja = [100.0], bool $digital = false, ?User $cliente = null): Order
    {
        $cliente ??= User::factory()->create();

        $order = Order::create([
            'user_id' => $cliente->id,
            'customer_name' => 'Cliente',
            'customer_whatsapp' => '(11)99999-0000',
            'delivery_type' => 'retirada',
            'items_total' => array_sum($valoresPorLoja),
            'shipping_total' => 0,
            'total_amount' => array_sum($valoresPorLoja),
            'status' => OrderStatus::AguardandoPagamento,
            'payment_status' => 'pending',
        ]);

        foreach ($valoresPorLoja as $valor) {
            $expositor = $this->expositor();

            $product = Product::factory()->create([
                'expositor_id' => $expositor->id,
                'item_type' => $digital ? 'servico' : 'produto',
                'name' => 'Item '.self::$counter,
                'slug' => 'pag-item-'.self::$counter,
                'price' => $valor,
                'is_digital' => $digital,
            ]);

            if ($digital) {
                AvaCourse::create([
                    'product_id' => $product->id,
                    'published_at' => now()->subDay(),
                    'certificate_enabled' => false,
                ]);
            }

            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'product_offer_id' => $product->offers()->first()->id,
                'expositor_id' => $expositor->id,
                'expositor_name' => $expositor->name,
                'product_name' => $product->name,
                'unit_price' => $valor,
                'quantity' => 1,
                'total_price' => $valor,
            ]);

            OrderSplit::create([
                'order_id' => $order->id,
                'expositor_id' => $expositor->id,
                'expositor_name' => $expositor->name,
                'gross_amount' => $valor,
                'commission_percent' => 0,
                'commission_amount' => 0,
                'net_amount' => $valor,
                'shipping_amount' => 0,
                'status' => OrderSplitStatus::Pendente,
            ]);
        }

        return $order->fresh(['items', 'splits']);
    }

    private function fingirPagamentoAprovado(Order $order, string $paymentId = '999', ?float $valor = null): void
    {
        Http::fake([
            'api.mercadopago.com/v1/payments/'.$paymentId => Http::response([
                'id' => (int) $paymentId,
                'status' => 'approved',
                'external_reference' => $order->reference,
                'transaction_amount' => $valor ?? (float) $order->total_amount,
                'date_approved' => '2026-06-29T12:00:00.000-03:00',
            ]),
        ]);
    }

    private function webhook(string $paymentId = '999'): TestResponse
    {
        return $this->postJson(route('mercado-pago.webhook'), [
            'type' => 'payment',
            'data' => ['id' => $paymentId],
        ]);
    }

    // ─── F-03: o pagamento online precisa disparar o evento ─────────────────

    public function test_pagamento_aprovado_dispara_o_evento_de_split_confirmado(): void
    {
        $order = $this->pedido([100.0]);
        $this->fingirPagamentoAprovado($order);

        Event::fake([OrderSplitConfirmed::class]);

        $this->webhook()->assertOk();

        // Sem isto, nenhum efeito de negócio do split acontece no pagamento
        // online — era o F-03.
        Event::assertDispatched(OrderSplitConfirmed::class, 1);
    }

    public function test_curso_digital_e_liberado_pelo_pagamento_online_sem_acao_do_lojista(): void
    {
        $cliente = User::factory()->create();
        $order = $this->pedido([100.0], digital: true, cliente: $cliente);
        $this->fingirPagamentoAprovado($order);

        $this->webhook()->assertOk();

        $this->assertSame(1, AvaEnrollment::where('user_id', $cliente->id)->count());
    }

    // ─── Atomicidade ────────────────────────────────────────────────────────

    public function test_order_e_splits_confirmam_juntos(): void
    {
        $order = $this->pedido([100.0, 200.0]);
        $this->fingirPagamentoAprovado($order);

        $this->webhook()->assertOk();

        $order->refresh();
        $this->assertSame(OrderStatus::PagamentoConfirmado, $order->status);
        $this->assertSame('approved', $order->payment_status);
        $this->assertNotNull($order->paid_at);

        foreach ($order->splits as $split) {
            $this->assertSame(OrderSplitStatus::Confirmado, $split->status);
            $this->assertNotNull($split->confirmed_at);
        }
    }

    // ─── Idempotência ───────────────────────────────────────────────────────

    public function test_webhook_repetido_nao_duplica_efeito(): void
    {
        $cliente = User::factory()->create();
        $order = $this->pedido([100.0], digital: true, cliente: $cliente);
        $this->fingirPagamentoAprovado($order);

        $this->webhook()->assertOk();
        $primeiraConfirmacao = $order->fresh()->paid_at;

        Event::fake([OrderSplitConfirmed::class]);
        $this->webhook()->assertOk();

        // Segunda confirmação: mesmo estado, nenhum efeito novo.
        Event::assertNotDispatched(OrderSplitConfirmed::class);
        $this->assertSame(1, AvaEnrollment::where('user_id', $cliente->id)->count());
        $this->assertEquals($primeiraConfirmacao, $order->fresh()->paid_at);
    }

    public function test_paid_at_nao_e_reescrito_por_confirmacao_posterior(): void
    {
        $order = $this->pedido([100.0]);

        // Primeiro pagamento, com data conhecida.
        $this->fingirPagamentoAprovado($order);
        $this->webhook()->assertOk();
        $original = $order->fresh()->paid_at;

        // Uma segunda notificação, agora sem `date_approved` — o caminho que
        // caía em `now()` e reescrevia o momento do pagamento.
        Http::fake([
            'api.mercadopago.com/v1/payments/999' => Http::response([
                'id' => 999,
                'status' => 'approved',
                'external_reference' => $order->reference,
                'transaction_amount' => (float) $order->total_amount,
            ]),
        ]);

        $this->webhook()->assertOk();

        $this->assertEquals($original, $order->fresh()->paid_at);
    }

    // ─── Status que não confirmam ───────────────────────────────────────────

    /**
     * @return array<string, array{0: string}>
     */
    public static function statusQueNaoConfirmam(): array
    {
        return [
            'pendente' => ['pending'],
            'em processamento' => ['in_process'],
            'recusado' => ['rejected'],
            'desconhecido' => ['status_que_nao_existe'],
        ];
    }

    #[DataProvider('statusQueNaoConfirmam')]
    public function test_status_nao_aprovado_nao_confirma_o_pedido(string $status): void
    {
        $order = $this->pedido([100.0]);

        Http::fake([
            'api.mercadopago.com/v1/payments/999' => Http::response([
                'id' => 999,
                'status' => $status,
                'external_reference' => $order->reference,
                'transaction_amount' => (float) $order->total_amount,
            ]),
        ]);

        $this->webhook()->assertOk();

        $order->refresh();
        $this->assertNotSame(OrderStatus::PagamentoConfirmado, $order->status);
        $this->assertNull($order->paid_at);
        $this->assertSame(OrderSplitStatus::Pendente, $order->splits->first()->status);
    }

    // ─── Valor divergente ───────────────────────────────────────────────────

    public function test_pagamento_de_valor_menor_nao_confirma_o_pedido(): void
    {
        $order = $this->pedido([500.0]);

        // Aprovado no gateway, mas de R$ 1 num pedido de R$ 500.
        $this->fingirPagamentoAprovado($order, valor: 1.0);

        $this->webhook();

        $order->refresh();
        $this->assertNotSame(OrderStatus::PagamentoConfirmado, $order->status);
        $this->assertNull($order->paid_at);
        $this->assertSame(OrderSplitStatus::Pendente, $order->splits->first()->status);
    }

    // ─── Atomicidade sob falha ──────────────────────────────────────────────

    public function test_falha_no_segundo_split_desfaz_o_pagamento_inteiro(): void
    {
        $order = $this->pedido([100.0, 200.0]);
        $this->fingirPagamentoAprovado($order);

        // Quebra a confirmação do segundo split, no meio da transição.
        $confirmados = 0;
        OrderSplit::saving(function () use (&$confirmados) {
            $confirmados++;

            if ($confirmados === 2) {
                throw new \RuntimeException('falha proposital no segundo split');
            }
        });

        $this->webhook();

        // Nada pela metade: nem pedido pago, nem o primeiro split confirmado.
        $order->refresh();
        $this->assertNotSame(OrderStatus::PagamentoConfirmado, $order->status);
        $this->assertNull($order->paid_at);

        foreach ($order->splits as $split) {
            $this->assertSame(OrderSplitStatus::Pendente, $split->status);
            $this->assertNull($split->confirmed_at);
        }
    }

    public function test_falha_ao_salvar_o_pedido_nao_confirma_split_algum(): void
    {
        $order = $this->pedido([100.0, 200.0]);
        $this->fingirPagamentoAprovado($order);

        Order::saving(function (Order $salvando) {
            if ($salvando->status === OrderStatus::PagamentoConfirmado) {
                throw new \RuntimeException('falha proposital ao marcar o pedido como pago');
            }
        });

        $this->webhook();

        $order->refresh();
        $this->assertNotSame(OrderStatus::PagamentoConfirmado, $order->status);

        foreach ($order->splits as $split) {
            $this->assertSame(OrderSplitStatus::Pendente, $split->status);
        }
    }

    public function test_falha_no_listener_nao_desfaz_o_pagamento(): void
    {
        $order = $this->pedido([100.0]);
        $this->fingirPagamentoAprovado($order);

        // O evento sai depois do commit: um efeito colateral que falha — um
        // e-mail, por exemplo — não pode desfazer um pagamento real.
        Event::listen(OrderSplitConfirmed::class, function () {
            throw new \RuntimeException('falha proposital no listener');
        });

        try {
            $this->webhook();
        } catch (\Throwable) {
            // O erro do listener pode subir; o que importa é o estado.
        }

        $order->refresh();
        $this->assertSame(OrderStatus::PagamentoConfirmado, $order->status);
        $this->assertSame(OrderSplitStatus::Confirmado, $order->splits->first()->status);
    }

    // ─── Dois caminhos confirmando o mesmo pedido ───────────────────────────

    public function test_pagamento_pelo_brick_e_webhook_produzem_uma_transicao_so(): void
    {
        $cliente = User::factory()->create();
        $order = $this->pedido([100.0], digital: true, cliente: $cliente);

        // O Brick cria o pagamento e ja aplica o resultado...
        Http::fake([
            'api.mercadopago.com/v1/payments' => Http::response([
                'id' => 999,
                'status' => 'approved',
                'external_reference' => $order->reference,
                'transaction_amount' => (float) $order->total_amount,
                'date_approved' => '2026-06-29T12:00:00.000-03:00',
            ]),
            'api.mercadopago.com/v1/payments/999' => Http::response([
                'id' => 999,
                'status' => 'approved',
                'external_reference' => $order->reference,
                'transaction_amount' => (float) $order->total_amount,
                'date_approved' => '2026-06-29T12:00:00.000-03:00',
            ]),
        ]);

        app(MercadoPagoService::class)->createPayment($order, [
            'payment_method_id' => 'pix',
            'payer' => ['email' => 'cliente@teste.com'],
        ]);

        $primeira = $order->fresh()->paid_at;

        // ...e o webhook chega logo depois, para o mesmo pagamento.
        $this->webhook()->assertOk();

        $this->assertEquals($primeira, $order->fresh()->paid_at);
        $this->assertSame(1, AvaEnrollment::where('user_id', $cliente->id)->count());
    }

    // ─── Revisão adversarial ────────────────────────────────────────────────

    public function test_confirmar_o_mesmo_split_duas_vezes_dispara_um_evento_so(): void
    {
        $order = $this->pedido([100.0]);
        $split = $order->splits->first();

        Event::fake([OrderSplitConfirmed::class]);

        // O lojista clica duas vezes, ou dois requests chegam juntos. O evento
        // representa a transicao pendente → confirmado, nao a chamada do metodo.
        $split->confirmar();
        $split->confirmar();

        Event::assertDispatched(OrderSplitConfirmed::class, 1);
    }

    public function test_pagamento_nao_pode_confirmar_um_pedido_de_outro(): void
    {
        $pedidoA = $this->pedido([100.0]);
        $pedidoB = $this->pedido([100.0]);

        // O pagamento aponta para A; nada deve alcancar B.
        $this->fingirPagamentoAprovado($pedidoA);
        $this->webhook()->assertOk();

        $this->assertSame(OrderStatus::PagamentoConfirmado, $pedidoA->fresh()->status);
        $this->assertNotSame(OrderStatus::PagamentoConfirmado, $pedidoB->fresh()->status);
        $this->assertNull($pedidoB->fresh()->paid_at);
        $this->assertSame(OrderSplitStatus::Pendente, $pedidoB->splits->first()->fresh()->status);
    }

    public function test_pedido_cancelado_nao_ressuscita_com_pagamento_atrasado(): void
    {
        $order = $this->pedido([100.0]);
        $order->forceFill(['status' => OrderStatus::Cancelado])->save();

        $this->fingirPagamentoAprovado($order);
        $this->webhook();

        // Um aprovado que chega tarde nao pode reabrir um pedido terminal.
        $order->refresh();
        $this->assertSame(OrderStatus::Cancelado, $order->status);
        $this->assertNull($order->paid_at);
        $this->assertSame(OrderSplitStatus::Pendente, $order->splits->first()->status);
    }

    public function test_pagamento_de_valor_maior_nao_confirma_o_pedido(): void
    {
        $order = $this->pedido([500.0]);

        // Aprovado por mais do que o pedido custa: divergencia e divergencia,
        // para cima ou para baixo.
        $this->fingirPagamentoAprovado($order, valor: 999.99);
        $this->webhook();

        $order->refresh();
        $this->assertNotSame(OrderStatus::PagamentoConfirmado, $order->status);
        $this->assertNull($order->paid_at);
    }

    public function test_valor_exato_confirma(): void
    {
        $order = $this->pedido([500.0]);
        $this->fingirPagamentoAprovado($order, valor: 500.00);

        $this->webhook()->assertOk();

        $this->assertSame(OrderStatus::PagamentoConfirmado, $order->fresh()->status);
    }

    public function test_rollback_nao_deixa_escapar_o_evento(): void
    {
        $order = $this->pedido([100.0, 200.0]);
        $this->fingirPagamentoAprovado($order);

        $confirmados = 0;
        OrderSplit::saving(function () use (&$confirmados) {
            $confirmados++;

            if ($confirmados === 2) {
                throw new \RuntimeException('falha proposital');
            }
        });

        Event::fake([OrderSplitConfirmed::class]);

        $this->webhook();

        // O evento e registrado em afterCommit: transacao abortada, nenhum
        // efeito escapa.
        Event::assertNotDispatched(OrderSplitConfirmed::class);
    }

    public function test_outro_pagamento_nao_reescreve_o_registro_de_um_pedido_ja_pago(): void
    {
        $order = $this->pedido([100.0]);

        $this->fingirPagamentoAprovado($order, paymentId: '111');
        $this->postJson(route('mercado-pago.webhook'), ['type' => 'payment', 'data' => ['id' => '111']])->assertOk();

        $pago = $order->fresh();
        $this->assertSame('111', $pago->mercado_pago_payment_id);

        // Um segundo pagamento, com outro id e outro valor, chega para o mesmo
        // pedido ja pago. Ele nao pode reescrever o rastro do pagamento que de
        // fato quitou o pedido.
        Http::fake([
            'api.mercadopago.com/v1/payments/222' => Http::response([
                'id' => 222,
                'status' => 'approved',
                'external_reference' => $order->reference,
                'transaction_amount' => 1.0,
                'date_approved' => '2026-07-30T12:00:00.000-03:00',
            ]),
        ]);

        $this->postJson(route('mercado-pago.webhook'), ['type' => 'payment', 'data' => ['id' => '222']]);

        $depois = $order->fresh();
        $this->assertSame('111', $depois->mercado_pago_payment_id);
        $this->assertEquals($pago->paid_at, $depois->paid_at);
    }

    // ─── R-5: sem valor confiável não há confirmação ────────────────────────

    /**
     * @return array<string, array{0: mixed}>
     */
    public static function valoresNaoConfiaveis(): array
    {
        return [
            'ausente' => [null],
            'nulo explicito' => ['__null__'],
            'texto' => ['abc'],
            'vazio' => [''],
            'negativo' => [-1],
        ];
    }

    #[DataProvider('valoresNaoConfiaveis')]
    public function test_aprovado_sem_valor_confiavel_nao_confirma(mixed $valor): void
    {
        $order = $this->pedido([500.0]);

        $payload = [
            'id' => 999,
            'status' => 'approved',
            'external_reference' => $order->reference,
            'date_approved' => '2026-06-29T12:00:00.000-03:00',
        ];

        if ($valor !== null) {
            $payload['transaction_amount'] = $valor === '__null__' ? null : $valor;
        }

        Http::fake(['api.mercadopago.com/v1/payments/999' => Http::response($payload)]);

        $this->webhook();

        // Aprovado no gateway, mas sem valor legivel: fail closed.
        $order->refresh();
        $this->assertNotSame(OrderStatus::PagamentoConfirmado, $order->status);
        $this->assertNull($order->paid_at);
        $this->assertSame(OrderSplitStatus::Pendente, $order->splits->first()->status);
    }

    // ─── R-6: comparação determinística, em centavos ────────────────────────

    /**
     * @return array<string, array{0: float, 1: bool}>
     */
    public static function valoresLimitrofes(): array
    {
        return [
            'exato' => [500.00, true],
            'um centavo a menos' => [499.99, false],
            'um centavo a mais' => [500.01, false],
            'muito maior' => [999.99, false],
            'zero' => [0.0, false],
            // Frações menores que o centavo não existem em real: arredondam
            // para o centavo mais próximo antes da comparação.
            'milesimo para baixo' => [500.001, true],
            'milesimo para cima' => [500.006, false],
        ];
    }

    #[DataProvider('valoresLimitrofes')]
    public function test_comparacao_monetaria_em_centavos(float $pago, bool $deveConfirmar): void
    {
        $order = $this->pedido([500.0]);

        // Direto na ação: o tradutor do gateway já arredonda para duas casas, e
        // aqui interessa a regra do domínio, sem essa etapa no meio.
        $confirmacao = new PaymentConfirmation(
            provider: 'mercado_pago',
            externalPaymentId: '999',
            amount: $pago,
            paidAt: now(),
        );

        try {
            app(ConfirmOrderPayment::class)($order, $confirmacao);
        } catch (\RuntimeException) {
            // Recusa esperada nos casos divergentes.
        }

        $order->refresh();

        if ($deveConfirmar) {
            $this->assertSame(OrderStatus::PagamentoConfirmado, $order->status);
        } else {
            $this->assertNotSame(OrderStatus::PagamentoConfirmado, $order->status);
            $this->assertNull($order->paid_at);
        }
    }

    public function test_comparacao_nao_depende_da_representacao_binaria(): void
    {
        // 499.99 * 100 vale 49998.999999999993 em IEEE-754: um `(int)` direto
        // truncaria para 49998 e faria este pagamento passar por outro valor.
        $order = $this->pedido([499.99]);

        $confirmacao = new PaymentConfirmation(
            provider: 'mercado_pago',
            externalPaymentId: '999',
            amount: 499.99,
            paidAt: now(),
        );

        app(ConfirmOrderPayment::class)($order, $confirmacao);

        $this->assertSame(OrderStatus::PagamentoConfirmado, $order->fresh()->status);
    }

    // ─── Confirmação manual do lojista continua funcionando ─────────────────

    public function test_confirmacao_manual_do_lojista_continua_disparando_o_evento(): void
    {
        $order = $this->pedido([100.0]);
        $split = $order->splits->first();

        Event::fake([OrderSplitConfirmed::class]);

        $split->confirmar();

        Event::assertDispatched(OrderSplitConfirmed::class, 1);
        $this->assertSame(OrderSplitStatus::Confirmado, $split->fresh()->status);
    }
}
