<?php

namespace Tests\Feature;

use App\Actions\Payments\RegisterPaymentConflict;
use App\Enums\OrderSplitStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentConflictType;
use App\Enums\UserRole;
use App\Models\Expositor;
use App\Models\Order;
use App\Models\PaymentConflict;
use App\Models\Product;
use App\Models\ProductOffer;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\CartService;
use App\Services\OrderService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * FIN-SEC-01F-D — o dinheiro chegou e o pedido não pôde acompanhar.
 *
 * Fecha o V-6, cujo desenho era este:
 *
 *     gateway aprova → ConfirmOrderPayment tenta consumir estoque →
 *     não há → rollback → **nenhuma evidência de que o dinheiro existiu**
 *
 * A transação existe de propósito, e continua: ou o pedido vira pago inteiro ou
 * nada acontece. O que faltava era alguém registrar o "nada aconteceu" **fora**
 * dela. É por isso que `RegisterPaymentConflict` é chamada depois do rollback,
 * e nunca dentro da transação que ela documenta.
 */
class ConflitoDePagamentoTest extends TestCase
{
    use RefreshDatabase;

    private static int $counter = 0;

    /** @var array<string, array<string, mixed>> */
    private array $pagamentos = [];

    private bool $gatewayRegistrado = false;

    private function oferta(int $estoque = 10): ProductOffer
    {
        self::$counter++;

        $lojista = User::factory()->create(['role' => UserRole::Lojista, 'is_active' => true]);

        $expositor = Expositor::create([
            'user_id' => $lojista->id,
            'name' => 'Loja Conflito '.self::$counter,
            'slug' => 'conflito-loja-'.self::$counter,
            'is_active' => true,
        ]);

        $product = Product::factory()->create([
            'expositor_id' => $expositor->id,
            'item_type' => 'produto',
            'name' => 'Item Conflito '.self::$counter,
            'slug' => 'conflito-item-'.self::$counter,
            'price' => 100,
        ]);

        $offer = $product->offers()->first();
        $offer->update(['has_stock' => true, 'stock_quantity' => $estoque]);

        return $offer->fresh();
    }

    private function pedido(ProductOffer $offer, int $qty = 1): Order
    {
        $this->actingAs(User::factory()->create());

        app(CartService::class)->add($offer, $qty);

        return app(OrderService::class)->createFromCart([
            'customer_name' => 'Cliente',
            'customer_whatsapp' => '(11)99999-0000',
            'customer_email' => 'cliente@teste.com',
            'delivery_type' => 'retirada',
            'address_cep' => '01001000', 'address_rua' => 'Rua', 'address_numero' => '1',
            'address_bairro' => 'Centro', 'address_cidade' => 'Sao Paulo', 'address_estado' => 'SP',
            'shipping_total' => 0,
        ], app(CartService::class));
    }

    /** @param array<string, mixed> $extras */
    private function noGateway(string $paymentId, string $status, ?Order $order = null, array $extras = []): void
    {
        SiteSetting::instance()->update([
            'mercado_pago_ativo' => true,
            'mercado_pago_access_token' => 'TEST_TOKEN',
            'mercado_pago_sandbox' => true,
        ]);

        $this->pagamentos[$paymentId] = $extras + [
            'id' => (int) $paymentId,
            'status' => $status,
            'external_reference' => $order?->reference,
            'transaction_amount' => $order ? (float) $order->total_amount : 0.0,
            'date_approved' => '2026-08-29T12:00:00.000-03:00',
        ];

        if ($this->gatewayRegistrado) {
            return;
        }

        Http::fake(function ($request) {
            $id = (string) str($request->url())->afterLast('/');

            return Http::response($this->pagamentos[$id] ?? ['id' => (int) $id, 'status' => 'unknown']);
        });

        $this->gatewayRegistrado = true;
    }

    /** @param array<string, mixed> $data */
    private function notificar(string $tipo, array $data): void
    {
        $this->postJson(route('mercado-pago.webhook'), ['type' => $tipo, 'data' => $data]);
    }

    private function aprovar(Order $order, string $paymentId): void
    {
        $this->noGateway($paymentId, 'approved', $order);
        $this->notificar('payment', ['id' => $paymentId]);
    }

    /** Transforma o pedido num legado pré-01E: pagável, mas sem reserva. */
    private function semReserva(Order $order, ProductOffer $offer, int $estoqueRestante): void
    {
        Order::whereKey($order->getKey())->update(['stock_reserved_at' => null]);
        ProductOffer::whereKey($offer->id)->update([
            'reserved_quantity' => 0,
            'stock_quantity' => $estoqueRestante,
        ]);
    }

    // ─── V-6 — estoque insuficiente depois da captura ────────────────────────

    public function test_a_tabela_de_conflitos_existe(): void
    {
        $this->assertTrue(Schema::hasTable('payment_conflicts'));
    }

    public function test_estoque_insuficiente_apos_captura_gera_conflito_duravel(): void
    {
        $offer = $this->oferta(5);
        $order = $this->pedido($offer, 5);

        $this->semReserva($order, $offer, estoqueRestante: 1);

        $this->aprovar($order, '201');

        $order->refresh();

        // O que a 01E já fazia certo: não confirma, não consome nada.
        $this->assertSame(OrderStatus::AguardandoPagamento, $order->status);
        $this->assertNull($order->paid_at);
        $this->assertSame(1, $offer->fresh()->stock_quantity);
        $this->assertSame(OrderSplitStatus::Pendente, $order->splits->first()->fresh()->status);

        // O que faltava: o dinheiro capturado deixa rastro durável.
        $conflito = PaymentConflict::sole();

        $this->assertSame($order->id, $conflito->order_id);
        $this->assertSame(PaymentConflictType::InsufficientStock, $conflito->type);
        $this->assertSame('mercado_pago', $conflito->provider);
        $this->assertSame('201', $conflito->external_reference);
        $this->assertSame('500.00', $conflito->amount);
        $this->assertNull($conflito->resolved_at);
    }

    public function test_o_rollback_da_confirmacao_nao_apaga_o_conflito(): void
    {
        // O ponto inteiro do V-6: o conflito nasce de uma transação desfeita, e
        // precisa sobreviver a ela. Se fosse gravado lá dentro, sumiria junto.
        $offer = $this->oferta(5);
        $order = $this->pedido($offer, 5);

        $this->semReserva($order, $offer, estoqueRestante: 1);

        $this->aprovar($order, '202');

        // Nada da transição sobreviveu...
        $order->refresh();
        $this->assertNull($order->stock_consumed_at);
        $this->assertNotSame(OrderStatus::PagamentoConfirmado, $order->status);

        // ...e o registro do que foi desfeito, sim.
        $this->assertSame(1, PaymentConflict::where('order_id', $order->id)->count());
    }

    public function test_duplicidade_de_webhook_nao_cria_conflitos_multiplos(): void
    {
        $offer = $this->oferta(5);
        $order = $this->pedido($offer, 5);

        $this->semReserva($order, $offer, estoqueRestante: 1);

        $this->aprovar($order, '203');
        $this->notificar('payment', ['id' => '203']);
        $this->notificar('payment', ['id' => '203']);

        $this->assertSame(1, PaymentConflict::where('order_id', $order->id)->count());
    }

    // ─── Pagamento depois do fim ─────────────────────────────────────────────

    public function test_pagamento_depois_de_expirado_gera_conflito_proprio(): void
    {
        $offer = $this->oferta();
        $order = $this->pedido($offer, 2);

        Order::whereKey($order->getKey())->update([
            'status' => OrderStatus::Expirado->value,
            'payment_expires_at' => now()->subHour(),
        ]);

        $this->aprovar($order, '204');

        $order->refresh();

        // Não ressuscita: o estoque já voltou à prateleira e pode ter sido
        // vendido a outra pessoa.
        $this->assertSame(OrderStatus::Expirado, $order->status);
        $this->assertNull($order->paid_at);

        $conflito = PaymentConflict::sole();

        // Tipo próprio de propósito: quem reconcilia precisa saber que o
        // estoque já foi devolvido antes de decidir o que fazer com o dinheiro.
        $this->assertSame(PaymentConflictType::PaymentAfterExpiration, $conflito->type);
        $this->assertSame('204', $conflito->external_reference);
    }

    public function test_pagamento_depois_de_cancelado_gera_conflito(): void
    {
        $offer = $this->oferta();
        $order = $this->pedido($offer, 2);

        Order::whereKey($order->getKey())->update(['status' => OrderStatus::Cancelado->value]);

        $this->aprovar($order, '205');

        $order->refresh();

        $this->assertSame(OrderStatus::Cancelado, $order->status);
        $this->assertSame(PaymentConflictType::PaymentAfterTerminal, PaymentConflict::sole()->type);
    }

    public function test_valor_divergente_gera_conflito_de_amount_mismatch(): void
    {
        $offer = $this->oferta();
        $order = $this->pedido($offer, 2);

        // Aprovado no gateway por R$ 1 num pedido de R$ 200.
        $this->noGateway('206', 'approved', $order, ['transaction_amount' => 1.0]);
        $this->notificar('payment', ['id' => '206']);

        $order->refresh();

        $this->assertNotSame(OrderStatus::PagamentoConfirmado, $order->status);
        $this->assertNull($order->paid_at);

        $conflito = PaymentConflict::sole();

        $this->assertSame(PaymentConflictType::AmountMismatch, $conflito->type);
        $this->assertSame('1.00', $conflito->amount);
        // O contexto faz ida e volta por JSON, que devolve 200 e nao 200.0.
        $this->assertEquals(200.0, $conflito->context['total_do_pedido']);
    }

    // ─── Refund parcial ──────────────────────────────────────────────────────

    public function test_refund_parcial_nao_vira_estorno_total(): void
    {
        $offer = $this->oferta();
        $order = $this->pedido($offer, 1);

        $this->aprovar($order, '210');
        $this->assertSame(OrderStatus::PagamentoConfirmado, $order->fresh()->status);

        // Devolução de R$ 30 num pedido de R$ 100. O Mercado Pago mantém
        // `status: approved` e informa o que voltou à parte.
        $this->noGateway('210', 'approved', $order, ['transaction_amount_refunded' => 30.0]);
        $this->notificar('payment', ['id' => '210']);

        $order->refresh();

        // Transformar isso em `Estornado` afirmaria que os R$ 100 voltaram, e
        // reverteria o repasse inteiro do vendedor por causa de trinta reais.
        $this->assertSame(OrderStatus::PagamentoConfirmado, $order->status);
        $this->assertNull($order->reversed_at);
        $this->assertSame(OrderSplitStatus::Confirmado, $order->splits->first()->fresh()->status);
    }

    public function test_refund_parcial_registra_conflito_com_o_valor_devolvido(): void
    {
        $offer = $this->oferta();
        $order = $this->pedido($offer, 1);

        $this->aprovar($order, '211');

        $this->noGateway('211', 'approved', $order, ['transaction_amount_refunded' => 30.0]);
        $this->notificar('payment', ['id' => '211']);

        $conflito = PaymentConflict::sole();

        $this->assertSame(PaymentConflictType::PartialRefundUnsupported, $conflito->type);
        $this->assertSame('30.00', $conflito->amount);

        // Em centavos inteiros: comparar dinheiro com float é o erro que a
        // FIN-SEC-01D removeu de `ConfirmOrderPayment`.
        $this->assertSame(3000, $conflito->context['devolvido_em_centavos']);
        $this->assertSame(10000, $conflito->context['pago_em_centavos']);
    }

    public function test_refund_parcial_duplicado_e_idempotente(): void
    {
        $offer = $this->oferta();
        $order = $this->pedido($offer, 1);

        $this->aprovar($order, '212');

        $this->noGateway('212', 'approved', $order, ['transaction_amount_refunded' => 30.0]);
        $this->notificar('payment', ['id' => '212']);
        $this->notificar('payment', ['id' => '212']);
        $this->notificar('payment', ['id' => '212']);

        $this->assertSame(1, PaymentConflict::count());
        $this->assertSame(OrderStatus::PagamentoConfirmado, $order->fresh()->status);
    }

    public function test_devolucao_integral_nao_e_tratada_como_parcial(): void
    {
        $offer = $this->oferta();
        $order = $this->pedido($offer, 1);

        $this->aprovar($order, '213');

        // Devolveu exatamente o que foi pago: é reversão total.
        $this->noGateway('213', 'refunded', $order, ['transaction_amount_refunded' => 100.0]);
        $this->notificar('payment', ['id' => '213']);

        $this->assertSame(OrderStatus::Estornado, $order->fresh()->status);
        $this->assertSame(0, PaymentConflict::count());
    }

    public function test_devolucao_sem_valor_total_para_comparar_falha_fechada(): void
    {
        $offer = $this->oferta();
        $order = $this->pedido($offer, 1);

        $this->aprovar($order, '214');

        // Informou o que voltou, não informou o total. Sem os dois lados não dá
        // para afirmar integralidade: registra conflito em vez de estornar.
        $this->pagamentos['214'] = [
            'id' => 214,
            'status' => 'refunded',
            'external_reference' => $order->reference,
            'transaction_amount_refunded' => 50.0,
        ];
        $this->notificar('payment', ['id' => '214']);

        $this->assertSame(OrderStatus::PagamentoConfirmado, $order->fresh()->status);
        $this->assertSame(PaymentConflictType::PartialRefundUnsupported, PaymentConflict::sole()->type);
    }

    // ─── Chargeback ──────────────────────────────────────────────────────────

    public function test_chargeback_correlacionado_registra_conflito_sem_estornar(): void
    {
        $offer = $this->oferta();
        $order = $this->pedido($offer, 1);

        $this->aprovar($order, '220');

        $this->notificar('topic_chargebacks_wh', ['id' => 'CB-10', 'payment_id' => '220']);

        $order->refresh();

        // A notificação diz que existe contestação, não como ela terminou. Um
        // chargeback pode ser disputado e revertido, e `Estornado` não tem volta.
        $this->assertSame(OrderStatus::PagamentoConfirmado, $order->status);
        $this->assertNull($order->reversed_at);
        $this->assertSame(OrderSplitStatus::Confirmado, $order->splits->first()->fresh()->status);

        $conflito = PaymentConflict::sole();

        $this->assertSame(PaymentConflictType::ChargebackUnverified, $conflito->type);
        // A identidade guardada é a do chargeback; a do pagamento fica no
        // contexto. São recursos distintos (D-FIN-35).
        $this->assertSame('CB-10', $conflito->external_reference);
        $this->assertSame('220', $conflito->context['payment_id']);
        $this->assertSame('220', $order->mercado_pago_payment_id);
    }

    public function test_chargeback_consumado_no_pagamento_estorna_de_verdade(): void
    {
        $offer = $this->oferta();
        $order = $this->pedido($offer, 1);

        $this->aprovar($order, '221');

        // Quando a contestação se consuma, o gateway escreve o desfecho no
        // **pagamento** — e aí existe evidência para reverter.
        $this->noGateway('221', 'charged_back', $order);
        $this->notificar('payment', ['id' => '221']);

        $order->refresh();

        $this->assertSame(OrderStatus::Estornado, $order->status);
        $this->assertSame('charged_back', $order->payment_status);
        $this->assertSame('221', $order->mercado_pago_payment_id);
    }

    public function test_chargeback_sem_correlacao_falha_fechado(): void
    {
        $offer = $this->oferta();
        $order = $this->pedido($offer, 1);

        $this->aprovar($order, '222');

        $this->notificar('topic_chargebacks_wh', ['id' => 'CB-11', 'payment_id' => '999']);

        $order->refresh();

        $this->assertSame(OrderStatus::PagamentoConfirmado, $order->status);
        $this->assertSame('222', $order->mercado_pago_payment_id);
        $this->assertSame(0, PaymentConflict::count());
    }

    public function test_chargeback_duplicado_nao_multiplica_conflitos(): void
    {
        $offer = $this->oferta();
        $order = $this->pedido($offer, 1);

        $this->aprovar($order, '223');

        $this->notificar('topic_chargebacks_wh', ['id' => 'CB-12', 'payment_id' => '223']);
        $this->notificar('topic_chargebacks_wh', ['id' => 'CB-12', 'payment_id' => '223']);

        $this->assertSame(1, PaymentConflict::count());
    }

    // ─── A ação, e o registro em si ──────────────────────────────────────────

    public function test_registro_repetido_devolve_a_mesma_linha(): void
    {
        $order = $this->pedido($this->oferta(), 1);

        $acao = app(RegisterPaymentConflict::class);

        $primeiro = $acao($order, PaymentConflictType::AmountMismatch, 'mercado_pago', '300', 10.0);
        $segundo = $acao($order, PaymentConflictType::AmountMismatch, 'mercado_pago', '300', 10.0);

        $this->assertSame($primeiro->id, $segundo->id);
        $this->assertSame(1, PaymentConflict::count());
    }

    public function test_tipos_diferentes_no_mesmo_pagamento_sao_conflitos_diferentes(): void
    {
        $order = $this->pedido($this->oferta(), 1);

        $acao = app(RegisterPaymentConflict::class);

        $acao($order, PaymentConflictType::AmountMismatch, 'mercado_pago', '301');
        $acao($order, PaymentConflictType::InsufficientStock, 'mercado_pago', '301');

        $this->assertSame(2, PaymentConflict::count());
    }

    public function test_evento_sem_correlacao_recebe_sentinela_e_deduplica(): void
    {
        // Sem sentinela a coluna seria nula, e em MySQL dois NULLs são
        // distintos numa chave única: a deduplicação desligaria justamente nos
        // eventos sem identidade, que são os que mais chegam repetidos.
        $order = $this->pedido($this->oferta(), 1);

        $acao = app(RegisterPaymentConflict::class);

        $acao($order, PaymentConflictType::UnmatchedReversal, 'mercado_pago', null);
        $acao($order, PaymentConflictType::UnmatchedReversal, 'mercado_pago', null);

        $this->assertSame(1, PaymentConflict::count());
        $this->assertSame(
            RegisterPaymentConflict::SEM_CORRELACAO,
            PaymentConflict::sole()->external_reference,
        );
    }

    public function test_conflito_impede_a_exclusao_do_pedido(): void
    {
        // Evidência de que dinheiro se moveu não é composição do pedido: ela
        // precisa sobreviver a qualquer limpeza. A FK é RESTRICT de propósito.
        $order = $this->pedido($this->oferta(), 1);

        app(RegisterPaymentConflict::class)($order, PaymentConflictType::InsufficientStock, 'mercado_pago', '302');

        $this->expectException(QueryException::class);

        $order->delete();
    }

    public function test_conflito_aberto_e_resolvido_se_distinguem(): void
    {
        $order = $this->pedido($this->oferta(), 1);

        $acao = app(RegisterPaymentConflict::class);

        $aberto = $acao($order, PaymentConflictType::InsufficientStock, 'mercado_pago', '303');
        $acao($order, PaymentConflictType::AmountMismatch, 'mercado_pago', '303')
            ->update(['resolved_at' => now()]);

        $this->assertSame(1, PaymentConflict::aberto()->count());
        $this->assertSame($aberto->id, PaymentConflict::aberto()->sole()->id);
    }
}
