<?php

namespace Tests\Feature;

use App\Actions\Orders\CompleteOrder;
use App\Actions\Payments\ReverseOrderPayment;
use App\Enums\AvaEnrollmentStatus;
use App\Enums\OrderSplitStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentConflictType;
use App\Enums\ShippingStatus;
use App\Enums\UserRole;
use App\Exceptions\TransicaoDePedidoInvalida;
use App\Models\Ava\AvaCourse;
use App\Models\Ava\AvaEnrollment;
use App\Models\Ava\AvaLesson;
use App\Models\Ava\AvaLessonProgress;
use App\Models\Ava\AvaModule;
use App\Models\Expositor;
use App\Models\Order;
use App\Models\OrderShipping;
use App\Models\PaymentConflict;
use App\Models\Product;
use App\Models\ProductOffer;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\CartService;
use App\Services\MercadoPagoService;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * FIN-SEC-01F-D — o que acontece quando o dinheiro volta.
 *
 * Herda de `CicloPedidoAuditoriaTest` as provas de V-2, V-5 e V-8, agora
 * invertidas: onde a auditoria afirmava o comportamento errado, aqui está o
 * comportamento exigido.
 *
 * ## As três coisas que uma reversão não pode fazer
 *
 * 1. **Repor estoque.** O produto já saiu, e muitas vezes já foi entregue
 *    (D-FIN-31). `stock_quantity` fica onde está.
 * 2. **Apagar que houve pagamento.** `paid_at` responde "quando foi pago?", e a
 *    resposta continua verdadeira (D-FIN-32). `reversed_at` é outra coluna.
 * 3. **Confundir identidades.** `mercado_pago_payment_id` é do pagamento, nunca
 *    do estorno nem do chargeback (D-FIN-35).
 */
class ReversaoFinanceiraTest extends TestCase
{
    use RefreshDatabase;

    private static int $counter = 0;

    /** @var array<string, array<string, mixed>> */
    private array $pagamentos = [];

    private bool $gatewayRegistrado = false;

    private function oferta(bool $digital = false): ProductOffer
    {
        self::$counter++;

        $lojista = User::factory()->create(['role' => UserRole::Lojista, 'is_active' => true]);

        $expositor = Expositor::create([
            'user_id' => $lojista->id,
            'name' => 'Loja Reversao '.self::$counter,
            'slug' => 'reversao-loja-'.self::$counter,
            'is_active' => true,
        ]);

        $product = Product::factory()->create([
            'expositor_id' => $expositor->id,
            'item_type' => $digital ? 'servico' : 'produto',
            'name' => 'Item Reversao '.self::$counter,
            'slug' => 'reversao-item-'.self::$counter,
            'price' => 100,
            'is_digital' => $digital,
        ]);

        if ($digital) {
            AvaCourse::create([
                'product_id' => $product->id,
                'published_at' => now()->subDay(),
                'certificate_enabled' => false,
            ]);
        }

        $offer = $product->offers()->first();
        $offer->update(['has_stock' => true, 'stock_quantity' => 10]);

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

    /**
     * Gateway falso registrado uma única vez, consultando um mapa vivo.
     *
     * `Http::fake` mantém o primeiro stub casado para uma mesma URL, então
     * refazer o fake não mudaria a resposta — e o mesmo `payment_id` precisa
     * poder passar de `approved` a `refunded`, que é exatamente o ciclo aqui.
     *
     * @param  array<string, mixed>  $extras
     */
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

    private function pagar(Order $order, string $paymentId): void
    {
        $this->noGateway($paymentId, 'approved', $order);
        $this->notificar('payment', ['id' => $paymentId]);
    }

    private function estornar(Order $order, string $paymentId): void
    {
        $this->noGateway($paymentId, 'refunded', $order);
        $this->notificar('payment', ['id' => $paymentId]);
    }

    // ─── V-2 — a reversão transiciona o pedido ───────────────────────────────

    public function test_refund_total_correlacionado_estorna_o_pedido(): void
    {
        $offer = $this->oferta();
        $order = $this->pedido($offer, 2);

        $this->pagar($order, '101');

        $order->refresh();
        $pagoEm = $order->paid_at;
        $this->assertSame(8, $offer->fresh()->stock_quantity);

        $this->estornar($order, '101');

        $order->refresh();

        $this->assertSame(OrderStatus::Estornado, $order->status);
        $this->assertNotNull($order->reversed_at);

        // `paid_at` intacto: o pedido **foi** pago, e isso não deixou de ser
        // verdade porque o dinheiro voltou depois (D-FIN-32).
        $this->assertEquals($pagoEm, $order->paid_at);

        // Identidade do pagamento preservada (D-FIN-35).
        $this->assertSame('101', $order->mercado_pago_payment_id);
    }

    public function test_reversao_nao_repoe_estoque_fisico(): void
    {
        // O caso real: pago, baixado, enviado, entregue — e estornado depois.
        // Repor as unidades aqui criaria estoque que não existe na prateleira.
        $offer = $this->oferta();
        $order = $this->pedido($offer, 2);

        $this->pagar($order, '102');
        $this->assertSame(8, $offer->fresh()->stock_quantity);

        $this->estornar($order, '102');

        $offer->refresh();
        $order->refresh();

        $this->assertSame(8, $offer->stock_quantity);
        $this->assertSame(0, $offer->reserved_quantity);

        // E o registro de que as unidades saíram continua de pé.
        $this->assertNotNull($order->stock_consumed_at);
        $this->assertNull($order->stock_released_at);
    }

    public function test_refund_duplicado_e_idempotente(): void
    {
        $offer = $this->oferta();
        $order = $this->pedido($offer, 2);

        $this->pagar($order, '103');
        $this->estornar($order, '103');

        $primeiraReversao = $order->fresh()->reversed_at;

        $this->travel(5)->seconds();
        $this->notificar('payment', ['id' => '103']);

        $order->refresh();

        $this->assertSame(OrderStatus::Estornado, $order->status);
        $this->assertEquals($primeiraReversao, $order->reversed_at);
        $this->assertSame(8, $offer->fresh()->stock_quantity);
        $this->assertSame(1, $order->splits()->count());
    }

    public function test_refund_de_pagamento_diferente_nao_estorna(): void
    {
        $offer = $this->oferta();
        $order = $this->pedido($offer, 1);

        $this->pagar($order, '104');

        // Reversão que declara reverter OUTRO pagamento.
        $this->noGateway('105', 'refunded', $order, ['payment_id' => '888']);
        $this->notificar('payment', ['id' => '105']);

        $order->refresh();

        $this->assertSame(OrderStatus::PagamentoConfirmado, $order->status);
        $this->assertNull($order->reversed_at);
        $this->assertSame('104', $order->mercado_pago_payment_id);
        $this->assertSame(OrderSplitStatus::Confirmado, $order->splits->first()->fresh()->status);

        // Some do domínio operacional, mas não do registro.
        $this->assertDatabaseHas('payment_conflicts', [
            'order_id' => $order->id,
            'type' => PaymentConflictType::UnmatchedReversal->value,
            'external_reference' => '888',
        ]);
    }

    public function test_refund_sem_payment_id_nao_estorna_e_nao_registra_conflito(): void
    {
        // Sem `payment_id` não há pedido a encontrar: fail closed desde a 01F-A.
        // Registrar conflito exigiria um `order_id`, que não existe aqui.
        $offer = $this->oferta();
        $order = $this->pedido($offer, 1);

        $this->pagar($order, '106');

        $this->assertNull(app(MercadoPagoService::class)->applyRefund(['id' => 'RF-X']));

        $this->assertSame(OrderStatus::PagamentoConfirmado, $order->fresh()->status);
        $this->assertSame(0, PaymentConflict::count());
    }

    public function test_refund_de_pedido_nunca_pago_registra_conflito_sem_estornar(): void
    {
        $offer = $this->oferta();
        $order = $this->pedido($offer, 1);

        // Pagamento vigente gravado sem confirmação — é o que acontece quando o
        // gateway informa `pending` e o Pix nunca é pago.
        $this->noGateway('107', 'pending', $order);
        $this->notificar('payment', ['id' => '107']);

        $this->assertSame(OrderStatus::AguardandoPagamento, $order->fresh()->status);
        $this->assertSame('107', $order->fresh()->mercado_pago_payment_id);

        $this->estornar($order, '107');

        $order->refresh();

        // Estornar exige ter havido dinheiro. A matriz recusa, e a recusa vira
        // evidência em vez de exceção perdida no log.
        $this->assertSame(OrderStatus::AguardandoPagamento, $order->status);
        $this->assertNull($order->reversed_at);
        $this->assertDatabaseHas('payment_conflicts', [
            'order_id' => $order->id,
            'type' => PaymentConflictType::UnmatchedReversal->value,
        ]);
    }

    // ─── Concluído × estornado ───────────────────────────────────────────────

    public function test_pedido_concluido_pode_ser_estornado(): void
    {
        $offer = $this->oferta();
        $order = $this->pedido($offer, 1);

        $this->pagar($order, '108');

        app(CompleteOrder::class)($order->fresh());
        $this->assertSame(OrderStatus::Concluido, $order->fresh()->status);

        $this->estornar($order, '108');

        $this->assertSame(OrderStatus::Estornado, $order->fresh()->status);
    }

    public function test_estorno_de_pedido_entregue_preserva_a_evidencia_logistica(): void
    {
        // O motivo pelo qual `Concluido → Estornado` não mistura dimensões: a
        // verdade logística mora em `order_shippings`, e a reversão não a toca.
        $offer = $this->oferta();
        $order = $this->pedido($offer, 1);

        $this->pagar($order, '109');

        $split = $order->splits->first();

        $envio = OrderShipping::create([
            'order_id' => $order->id,
            'order_split_id' => $split->id,
            'expositor_id' => $split->expositor_id,
            'status' => ShippingStatus::Delivered,
            'shipped_at' => now()->subDays(3),
            'delivered_at' => now()->subDay(),
        ]);

        app(CompleteOrder::class)($order->fresh());

        $this->estornar($order, '109');

        $envio->refresh();
        $order->refresh();

        $this->assertSame(OrderStatus::Estornado, $order->status);
        $this->assertSame(ShippingStatus::Delivered, $envio->status);
        $this->assertNotNull($envio->delivered_at);
        $this->assertNotNull($order->paid_at);
    }

    // ─── V-5 — splits ────────────────────────────────────────────────────────

    public function test_split_confirmado_vira_revertido(): void
    {
        $offer = $this->oferta();
        $order = $this->pedido($offer, 1);

        $this->pagar($order, '110');

        $split = $order->splits->first();
        $this->assertSame(OrderSplitStatus::Confirmado, $split->fresh()->status);
        $confirmadoEm = $split->fresh()->confirmed_at;

        $this->estornar($order, '110');

        $split->refresh();

        $this->assertSame(OrderSplitStatus::Revertido, $split->status);
        $this->assertNotNull($split->reverted_at);

        // `confirmed_at` responde "quando o repasse passou a ser devido?", e a
        // resposta continua verdadeira — como `paid_at` no pedido.
        $this->assertEquals($confirmadoEm, $split->confirmed_at);
    }

    public function test_split_revertido_permanece_revertido_em_webhook_duplicado(): void
    {
        $offer = $this->oferta();
        $order = $this->pedido($offer, 1);

        $this->pagar($order, '111');
        $this->estornar($order, '111');

        $revertidoEm = $order->splits->first()->fresh()->reverted_at;

        $this->travel(5)->seconds();
        $this->notificar('payment', ['id' => '111']);

        $split = $order->splits->first()->fresh();

        $this->assertSame(OrderSplitStatus::Revertido, $split->status);
        $this->assertEquals($revertidoEm, $split->reverted_at);
    }

    public function test_split_revertido_nao_volta_para_confirmado_por_novo_approved(): void
    {
        $offer = $this->oferta();
        $order = $this->pedido($offer, 1);

        $this->pagar($order, '112');
        $this->estornar($order, '112');

        // Um `approved` atrasado do mesmo pagamento chega depois da reversão.
        $this->noGateway('112', 'approved', $order);
        $this->notificar('payment', ['id' => '112']);

        $this->assertSame(OrderStatus::Estornado, $order->fresh()->status);
        $this->assertSame(OrderSplitStatus::Revertido, $order->splits->first()->fresh()->status);

        // E o dinheiro que chegou depois do fim vira conflito, não ressurreição.
        $this->assertDatabaseHas('payment_conflicts', [
            'order_id' => $order->id,
            'type' => PaymentConflictType::PaymentAfterTerminal->value,
        ]);
    }

    // ─── V-8 — acesso digital ────────────────────────────────────────────────

    public function test_pagamento_ativa_a_matricula(): void
    {
        $curso = $this->oferta(digital: true);
        $order = $this->pedido($curso, 1);

        $this->pagar($order, '120');

        $matricula = AvaEnrollment::first();

        $this->assertNotNull($matricula);
        $this->assertSame(AvaEnrollmentStatus::Active, $matricula->status);
        $this->assertTrue($matricula->isAccessible());
    }

    public function test_refund_total_revoga_o_acesso_ao_curso(): void
    {
        $curso = $this->oferta(digital: true);
        $order = $this->pedido($curso, 1);

        $this->pagar($order, '121');
        $matricula = AvaEnrollment::first();

        $this->estornar($order, '121');

        $matricula->refresh();

        $this->assertSame(AvaEnrollmentStatus::Refunded, $matricula->status);
        $this->assertFalse($matricula->isAccessible());
    }

    public function test_revogacao_preserva_progresso_e_historico(): void
    {
        $curso = $this->oferta(digital: true);
        $order = $this->pedido($curso, 1);

        $this->pagar($order, '122');
        $matricula = AvaEnrollment::first();

        $modulo = AvaModule::create([
            'course_id' => $matricula->course_id,
            'title' => 'Modulo 1',
            'sort_order' => 1,
        ]);

        $aula = AvaLesson::create([
            'module_id' => $modulo->id,
            'title' => 'Aula 1',
            'sort_order' => 1,
        ]);

        AvaLessonProgress::create([
            'enrollment_id' => $matricula->id,
            'lesson_id' => $aula->id,
            'completed_at' => now(),
        ]);

        $matricula->update(['completion_percent' => 100, 'completed_at' => now()]);
        $matriculadoEm = $matricula->enrolled_at;

        $this->estornar($order, '122');

        $matricula->refresh();

        // Revogado o direito de acesso; preservado tudo o que aconteceu.
        $this->assertSame(AvaEnrollmentStatus::Refunded, $matricula->status);
        $this->assertSame(1, $matricula->progress()->count());
        $this->assertSame(100.0, $matricula->completion_percent);
        $this->assertNotNull($matricula->completed_at);
        $this->assertEquals($matriculadoEm, $matricula->enrolled_at);
    }

    public function test_refund_duplicado_nao_revoga_duas_vezes(): void
    {
        $curso = $this->oferta(digital: true);
        $order = $this->pedido($curso, 1);

        $this->pagar($order, '123');
        $this->estornar($order, '123');

        $matricula = AvaEnrollment::first();
        $revogadoEm = $matricula->updated_at;

        $this->travel(5)->seconds();
        $this->notificar('payment', ['id' => '123']);

        $matricula->refresh();

        $this->assertSame(AvaEnrollmentStatus::Refunded, $matricula->status);
        $this->assertEquals($revogadoEm, $matricula->updated_at);
        $this->assertSame(1, AvaEnrollment::count());
    }

    // ─── A ação de domínio, direto ───────────────────────────────────────────

    public function test_a_acao_recusa_reverter_pedido_que_nunca_foi_pago(): void
    {
        $order = $this->pedido($this->oferta(), 1);

        $this->expectException(TransicaoDePedidoInvalida::class);

        app(ReverseOrderPayment::class)($order);
    }

    public function test_a_acao_nao_chama_o_gateway(): void
    {
        $offer = $this->oferta();
        $order = $this->pedido($offer, 1);

        $this->pagar($order, '130');

        Http::fake();

        app(ReverseOrderPayment::class)($order->fresh());

        // Reverter é registrar um estorno que já aconteceu lá fora, nunca pedir
        // um novo. Se esta ação disparasse dinheiro de volta, toda reentrega de
        // webhook viraria um estorno adicional.
        Http::assertNothingSent();
        $this->assertSame(OrderStatus::Estornado, $order->fresh()->status);
    }

    public function test_split_pendente_tambem_e_revertido(): void
    {
        $offer = $this->oferta();
        $order = $this->pedido($offer, 1);

        $this->pagar($order, '131');

        // Força a situação em que o split não acompanhou a confirmação.
        $order->splits()->update([
            'status' => OrderSplitStatus::Pendente->value,
            'confirmed_at' => null,
        ]);

        app(ReverseOrderPayment::class)($order->fresh());

        // Um repasse pendente num pedido estornado apareceria ao lojista como
        // dinheiro a caminho de uma venda que não existe mais.
        $this->assertSame(OrderSplitStatus::Revertido, $order->splits->first()->fresh()->status);
    }
}
