<?php

namespace Tests\Feature;

use App\Actions\Orders\CompleteOrder;
use App\Enums\AvaEnrollmentStatus;
use App\Enums\OrderSplitStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentConflictType;
use App\Enums\ShippingStatus;
use App\Enums\UserRole;
use App\Models\Ava\AvaCourse;
use App\Models\Ava\AvaEnrollment;
use App\Models\Expositor;
use App\Models\Order;
use App\Models\OrderShipping;
use App\Models\PaymentConflict;
use App\Models\Product;
use App\Models\ProductOffer;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\CartService;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * FIN-SEC-01F-D.1 — `cancelled` depois do pagamento não é prova de estorno.
 *
 * O Mercado Pago usa `cancelled` para situações diferentes, e a 01F-D lia o
 * caso mais grave da forma mais destrutiva possível: um `cancelled` sobre
 * pedido pago virava `Estornado`, revertia o repasse do vendedor e revogava o
 * acesso do aluno.
 *
 * O evento não sustenta nada disso. Refund tem status próprio (`refunded`),
 * valor (`transaction_amount_refunded`) e recurso próprio; `cancelled` não traz
 * nenhum dos três. E `Estornado` é terminal — o erro não teria desfazimento.
 *
 * Três destinos, portanto, e não dois:
 *
 *     cancelled antes da confirmação  →  cancelamento comercial (CancelOrder)
 *     cancelled depois da confirmação →  conflito, nenhuma transição
 *     refunded correlacionado         →  reversão financeira
 */
class CancelamentoAposPagamentoTest extends TestCase
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
            'name' => 'Loja Cancelamento '.self::$counter,
            'slug' => 'cancelamento-loja-'.self::$counter,
            'is_active' => true,
        ]);

        $product = Product::factory()->create([
            'expositor_id' => $expositor->id,
            'item_type' => $digital ? 'servico' : 'produto',
            'name' => 'Item Cancelamento '.self::$counter,
            'slug' => 'cancelamento-item-'.self::$counter,
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

    private function pagar(Order $order, string $paymentId): void
    {
        $this->noGateway($paymentId, 'approved', $order);
        $this->notificar('payment', ['id' => $paymentId]);
    }

    private function cancelarNoGateway(Order $order, string $paymentId): void
    {
        $this->noGateway($paymentId, 'cancelled', $order);
        $this->notificar('payment', ['id' => $paymentId]);
    }

    // ─── Antes do pagamento: nada muda ───────────────────────────────────────

    public function test_cancelled_antes_do_pagamento_continua_cancelando(): void
    {
        $offer = $this->oferta();
        $order = $this->pedido($offer, 2);

        $this->assertSame(2, $offer->fresh()->reserved_quantity);

        $this->cancelarNoGateway($order, '401');

        $order->refresh();

        // O fluxo aprovado na 01F-B segue intacto: encerra a intenção e devolve
        // a reserva na mesma transação.
        $this->assertSame(OrderStatus::Cancelado, $order->status);
        $this->assertNotNull($order->stock_released_at);
        $this->assertSame(10, $offer->fresh()->stock_quantity);
        $this->assertSame(0, $offer->fresh()->reserved_quantity);

        // E não é conflito: cancelar o que ninguém pagou é operação normal.
        $this->assertSame(0, PaymentConflict::count());
    }

    // ─── Depois do pagamento: nada muda, e fica registrado ───────────────────

    public function test_cancelled_apos_pagamento_confirmado_nao_estorna(): void
    {
        $offer = $this->oferta();
        $order = $this->pedido($offer, 2);

        $this->pagar($order, '410');

        $order->refresh();
        $pagoEm = $order->paid_at;
        $split = $order->splits->first();
        $confirmadoEm = $split->fresh()->confirmed_at;

        $this->cancelarNoGateway($order, '410');

        $order->refresh();

        // Estado comercial e financeiro, intactos.
        $this->assertSame(OrderStatus::PagamentoConfirmado, $order->status);
        $this->assertEquals($pagoEm, $order->paid_at);
        $this->assertNull($order->reversed_at);
        $this->assertSame('410', $order->mercado_pago_payment_id);

        // Repasse do vendedor, intacto.
        $this->assertSame(OrderSplitStatus::Confirmado, $split->fresh()->status);
        $this->assertEquals($confirmadoEm, $split->fresh()->confirmed_at);
        $this->assertNull($split->fresh()->reverted_at);

        // Estoque, intacto: nem devolvido, nem reconsumido.
        $this->assertSame(8, $offer->fresh()->stock_quantity);
        $this->assertSame(0, $offer->fresh()->reserved_quantity);
        $this->assertNotNull($order->stock_consumed_at);
        $this->assertNull($order->stock_released_at);
    }

    public function test_cancelled_apos_pagamento_registra_conflito_proprio(): void
    {
        $offer = $this->oferta();
        $order = $this->pedido($offer, 1);

        $this->pagar($order, '411');
        $this->cancelarNoGateway($order, '411');

        $conflito = PaymentConflict::sole();

        $this->assertSame($order->id, $conflito->order_id);
        $this->assertSame(PaymentConflictType::UnexpectedCancellationAfterPayment, $conflito->type);
        $this->assertSame('mercado_pago', $conflito->provider);

        // A identidade é a do pagamento do evento — o mesmo que quitou o
        // pedido —, nunca um id derivado.
        $this->assertSame('411', $conflito->external_reference);
        $this->assertSame('411', $conflito->context['pagamento_vigente']);
        $this->assertSame('pagamento_confirmado', $conflito->context['estado_do_pedido']);
        $this->assertNull($conflito->resolved_at);
    }

    public function test_cancelled_apos_pagamento_nao_revoga_acesso_ao_curso(): void
    {
        $curso = $this->oferta(digital: true);
        $order = $this->pedido($curso, 1);

        $this->pagar($order, '412');

        $matricula = AvaEnrollment::first();
        $this->assertSame(AvaEnrollmentStatus::Active, $matricula->status);

        $this->cancelarNoGateway($order, '412');

        $matricula->refresh();

        // Trancar o aluno fora do curso a partir de um evento que não prova
        // devolução de dinheiro é o dano mais visível do caso.
        $this->assertSame(AvaEnrollmentStatus::Active, $matricula->status);
        $this->assertTrue($matricula->isAccessible());
        $this->assertSame(1, PaymentConflict::count());
    }

    // ─── Pedido concluído ────────────────────────────────────────────────────

    public function test_cancelled_apos_concluido_nao_estorna(): void
    {
        $offer = $this->oferta();
        $order = $this->pedido($offer, 1);

        $this->pagar($order, '420');

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
        $this->assertSame(OrderStatus::Concluido, $order->fresh()->status);

        $entregueEm = $envio->fresh()->delivered_at;

        $this->cancelarNoGateway($order, '420');

        $order->refresh();

        $this->assertSame(OrderStatus::Concluido, $order->status);
        $this->assertNotNull($order->paid_at);
        $this->assertNull($order->reversed_at);
        $this->assertSame(OrderSplitStatus::Confirmado, $split->fresh()->status);

        // A dimensão logística não é tocada.
        $this->assertSame(ShippingStatus::Delivered, $envio->fresh()->status);
        $this->assertEquals($entregueEm, $envio->fresh()->delivered_at);

        $this->assertSame(
            PaymentConflictType::UnexpectedCancellationAfterPayment,
            PaymentConflict::sole()->type,
        );
        $this->assertSame('concluido', PaymentConflict::sole()->context['estado_do_pedido']);
    }

    // ─── Idempotência ────────────────────────────────────────────────────────

    public function test_cancelled_repetido_apos_pagamento_gera_um_unico_conflito(): void
    {
        $offer = $this->oferta();
        $order = $this->pedido($offer, 1);

        $this->pagar($order, '430');

        $this->cancelarNoGateway($order, '430');
        $this->notificar('payment', ['id' => '430']);
        $this->notificar('payment', ['id' => '430']);

        $order->refresh();

        $this->assertSame(1, PaymentConflict::count());
        $this->assertSame(OrderStatus::PagamentoConfirmado, $order->status);
        $this->assertNull($order->reversed_at);
        $this->assertSame(OrderSplitStatus::Confirmado, $order->splits->first()->fresh()->status);
    }

    // ─── E o que continua sendo reversão de verdade ──────────────────────────

    public function test_refunded_continua_estornando(): void
    {
        // A correção separa `cancelled` de `refunded`; ela não afrouxa o
        // segundo. Um refund correlacionado reverte como na 01F-D.
        $offer = $this->oferta();
        $order = $this->pedido($offer, 1);

        $this->pagar($order, '440');

        $this->noGateway('440', 'refunded', $order);
        $this->notificar('payment', ['id' => '440']);

        $order->refresh();

        $this->assertSame(OrderStatus::Estornado, $order->status);
        $this->assertNotNull($order->reversed_at);
        $this->assertSame(OrderSplitStatus::Revertido, $order->splits->first()->fresh()->status);
        $this->assertSame(0, PaymentConflict::count());
    }

    public function test_cancelled_sobre_pedido_ja_estornado_nao_abre_conflito(): void
    {
        // O gateway repetindo o que o domínio já sabe. Abrir conflito aqui
        // encheria a fila de reconciliação de casos já reconciliados.
        $offer = $this->oferta();
        $order = $this->pedido($offer, 1);

        $this->pagar($order, '450');

        $this->noGateway('450', 'refunded', $order);
        $this->notificar('payment', ['id' => '450']);
        $this->assertSame(OrderStatus::Estornado, $order->fresh()->status);

        $revertidoEm = $order->fresh()->reversed_at;

        $this->noGateway('450', 'cancelled', $order);
        $this->notificar('payment', ['id' => '450']);

        $order->refresh();

        $this->assertSame(OrderStatus::Estornado, $order->status);
        $this->assertEquals($revertidoEm, $order->reversed_at);
        $this->assertSame(0, PaymentConflict::count());
    }
}
