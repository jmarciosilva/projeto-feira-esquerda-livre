<?php

namespace Tests\Feature;

use App\Enums\OrderSplitStatus;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
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
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * FIN-SEC-01F-A — cada evento financeiro chega ao fluxo que lhe corresponde.
 *
 * A guarda anterior perguntava só "este id é diferente do que pagou o pedido?".
 * A pergunta protegia o caso certo — um segundo `approved` não pode sobrescrever
 * o pagamento que quitou o pedido — mas confundia **identidade do recurso** com
 * **natureza do evento**: uma reversão legítima do pagamento vigente caía no
 * mesmo desvio e desaparecia dentro do JSON de auditoria.
 *
 * Aqui as duas perguntas são feitas, e separadas:
 *
 *     natureza    → qual fluxo trata este evento
 *     identidade  → este evento pertence a este pedido
 */
class EventosFinanceirosTest extends TestCase
{
    use RefreshDatabase;

    private static int $counter = 0;

    /** @var array<string, array<string, mixed>> */
    private array $pagamentos = [];

    private bool $gatewayRegistrado = false;

    private function oferta(): ProductOffer
    {
        self::$counter++;

        $lojista = User::factory()->create(['role' => UserRole::Lojista, 'is_active' => true]);

        $expositor = Expositor::create([
            'user_id' => $lojista->id,
            'name' => 'Loja Evento '.self::$counter,
            'slug' => 'evento-loja-'.self::$counter,
            'is_active' => true,
        ]);

        $product = Product::factory()->create([
            'expositor_id' => $expositor->id,
            'item_type' => 'produto',
            'name' => 'Item Evento '.self::$counter,
            'slug' => 'evento-item-'.self::$counter,
            'price' => 100,
        ]);

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
            'delivery_type' => 'retirada',
            'address_cep' => '01001000', 'address_rua' => 'Rua', 'address_numero' => '1',
            'address_bairro' => 'Centro', 'address_cidade' => 'Sao Paulo', 'address_estado' => 'SP',
            'shipping_total' => 0,
        ], app(CartService::class));
    }

    /**
     * Registra o gateway falso uma única vez, consultando um mapa vivo.
     *
     * `Http::fake` mantém o primeiro stub casado para uma mesma URL, então
     * refazer o fake não mudaria a resposta — e o mesmo `payment_id` precisa
     * poder passar de `approved` a `refunded`, que é justamente o ciclo em
     * exame aqui.
     *
     * @param  array<string, mixed>  $extras
     */
    private function pagamentoNoGateway(string $paymentId, string $status, ?Order $order = null, array $extras = []): void
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
        $this->pagamentoNoGateway($paymentId, 'approved', $order);
        $this->notificar('payment', ['id' => $paymentId]);
    }

    // ─── A — segundo approved ────────────────────────────────────────────────

    public function test_a_segundo_approved_nao_toma_o_lugar_do_primeiro(): void
    {
        $offer = $this->oferta();
        $order = $this->pedido($offer, 2);

        $this->pagar($order, '901');

        $order->refresh();
        $primeiroPaidAt = $order->paid_at;

        $this->assertSame(OrderStatus::PagamentoConfirmado, $order->status);
        $this->assertSame('901', $order->mercado_pago_payment_id);
        $this->assertSame(8, $offer->fresh()->stock_quantity);

        // Um segundo pagamento aprovado chega para o mesmo pedido.
        $this->pagamentoNoGateway('902', 'approved', $order);
        $this->notificar('payment', ['id' => '902']);

        $order->refresh();

        $this->assertSame('901', $order->mercado_pago_payment_id);
        $this->assertSame('approved', $order->payment_status);
        $this->assertEquals($primeiroPaidAt, $order->paid_at);
        $this->assertArrayHasKey('payment_ignorado_902', $order->payment_payload);

        // Sem segundo consumo de estoque e sem segunda confirmação de split.
        $this->assertSame(8, $offer->fresh()->stock_quantity);
        $this->assertSame(0, $offer->fresh()->reserved_quantity);
        $this->assertSame(OrderSplitStatus::Confirmado, $order->splits->first()->fresh()->status);
        $this->assertSame(1, $order->splits()->count());
    }

    // ─── B — refund sobre o mesmo payment id ────────────────────────────────

    public function test_b_refund_do_pagamento_vigente_alcanca_o_fluxo_de_reversao(): void
    {
        $offer = $this->oferta();
        $order = $this->pedido($offer, 1);

        $this->pagar($order, '911');

        // O mesmo recurso de pagamento passa a `refunded`.
        $this->pagamentoNoGateway('911', 'refunded', $order);
        $this->notificar('payment', ['id' => '911']);

        $order->refresh();

        // Chegou ao roteador de reversão: não foi engolido pela guarda de
        // segundo pagamento, e ficou registrado sob chave própria.
        $this->assertArrayHasKey('reversao_refunded_911', $order->payment_payload);
        $this->assertArrayNotHasKey('payment_ignorado_911', $order->payment_payload);

        // A transição comercial é da 01F-D: aqui o pedido ainda não muda.
        $this->assertSame(OrderStatus::PagamentoConfirmado, $order->status);
        $this->assertNotNull($order->paid_at);
        $this->assertSame(9, $offer->fresh()->stock_quantity);
    }

    public function test_b_reversao_nao_apaga_o_rastro_do_pagamento_que_quitou(): void
    {
        $offer = $this->oferta();
        $order = $this->pedido($offer, 1);

        $this->pagar($order, '912');
        $this->pagamentoNoGateway('912', 'refunded', $order);
        $this->notificar('payment', ['id' => '912']);

        $order->refresh();

        $this->assertSame('912', $order->mercado_pago_payment_id);
        $this->assertSame('approved', $order->payment_status);
        $this->assertSame('approved', $order->payment_payload['payment']['status']);
    }

    // ─── C — refund de outro pagamento ──────────────────────────────────────

    public function test_c_refund_de_pagamento_alheio_nao_muta_o_pedido(): void
    {
        $offer = $this->oferta();
        $order = $this->pedido($offer, 1);

        $this->pagar($order, '921');

        // Uma reversão que carrega o vínculo explícito com OUTRO pagamento.
        $this->pagamentoNoGateway('922', 'refunded', $order, ['payment_id' => '999']);
        $this->notificar('payment', ['id' => '922']);

        $order->refresh();

        $this->assertArrayHasKey('reversao_nao_relacionada_999', $order->payment_payload);
        $this->assertSame(OrderStatus::PagamentoConfirmado, $order->status);
        $this->assertSame('921', $order->mercado_pago_payment_id);
        $this->assertSame(OrderSplitStatus::Confirmado, $order->splits->first()->fresh()->status);
        $this->assertSame(9, $offer->fresh()->stock_quantity);
    }

    // ─── R-A1 — recurso de refund não é recurso de pagamento ────────────────

    public function test_ra1_refund_sem_payment_id_falha_fechado(): void
    {
        // `GET /v1/payments/{payment_id}/refunds/{refund_id}` devolve um objeto
        // cujo `id` é o do estorno. Assumir `refund.id === payment.id` faria o
        // domínio correlacionar por um id que não é de pagamento nenhum.
        $offer = $this->oferta();
        $order = $this->pedido($offer, 1);

        $this->pagar($order, '991');

        $servico = app(MercadoPagoService::class);

        $this->assertNull($servico->applyRefund(['id' => 'RF-1']));

        $order->refresh();
        $this->assertSame(OrderStatus::PagamentoConfirmado, $order->status);
        $this->assertSame('991', $order->mercado_pago_payment_id);
        $this->assertSame(OrderSplitStatus::Confirmado, $order->splits->first()->fresh()->status);
        $this->assertSame(9, $offer->fresh()->stock_quantity);
    }

    public function test_ra1_refund_correlaciona_pelo_payment_id_e_nunca_pelo_proprio_id(): void
    {
        $offer = $this->oferta();
        $order = $this->pedido($offer, 1);

        $this->pagar($order, '992');

        $servico = app(MercadoPagoService::class);

        // O id do refund coincide de propósito com um pagamento inexistente:
        // se ele fosse usado como correlação, nada seria encontrado.
        $servico->applyRefund(['id' => '992', 'payment_id' => '992']);

        $this->assertArrayHasKey('reversao_refunded_992', $order->fresh()->payment_payload);
    }

    public function test_ra1_refund_de_pagamento_desconhecido_nao_encontra_pedido(): void
    {
        $offer = $this->oferta();
        $order = $this->pedido($offer, 1);

        $this->pagar($order, '993');

        $servico = app(MercadoPagoService::class);

        $this->assertNull($servico->applyRefund(['id' => 'RF-2', 'payment_id' => '000']));
        $this->assertSame(OrderStatus::PagamentoConfirmado, $order->fresh()->status);
    }

    // ─── D — chargeback correlacionado ──────────────────────────────────────

    public function test_d_chargeback_do_pagamento_vigente_e_reconhecido(): void
    {
        $offer = $this->oferta();
        $order = $this->pedido($offer, 1);

        $this->pagar($order, '931');

        $this->notificar('topic_chargebacks_wh', ['id' => 'CB-1', 'payment_id' => '931']);

        $order->refresh();

        $this->assertArrayHasKey('reversao_charged_back_931', $order->payment_payload);
        $this->assertSame('CB-1', $order->payment_payload['reversao_charged_back_931']['chargeback_id']);
        $this->assertSame(OrderStatus::PagamentoConfirmado, $order->status);
    }

    public function test_d_o_chargeback_nao_e_procurado_como_pagamento(): void
    {
        // `data.id` identifica o chargeback. Tratá-lo como payment id faria o
        // domínio consultar um pagamento que não existe.
        $offer = $this->oferta();
        $order = $this->pedido($offer, 1);

        $this->pagar($order, '932');
        Http::fake();

        $this->notificar('topic_chargebacks_wh', ['id' => '932', 'payment_id' => '932']);

        Http::assertNothingSent();
        $this->assertArrayHasKey('reversao_charged_back_932', $order->fresh()->payment_payload);
    }

    // ─── E — chargeback não relacionado ─────────────────────────────────────

    public function test_e_chargeback_de_outro_pagamento_nao_toca_o_pedido(): void
    {
        $offer = $this->oferta();
        $order = $this->pedido($offer, 1);

        $this->pagar($order, '941');

        $this->notificar('topic_chargebacks_wh', ['id' => 'CB-2', 'payment_id' => '888']);

        $order->refresh();

        $this->assertSame(OrderStatus::PagamentoConfirmado, $order->status);
        $this->assertSame('941', $order->mercado_pago_payment_id);
        $this->assertArrayNotHasKey('reversao_charged_back_888', $order->payment_payload);
        $this->assertSame(9, $offer->fresh()->stock_quantity);
    }

    public function test_e_chargeback_sem_payment_id_nao_encontra_pedido(): void
    {
        $offer = $this->oferta();
        $order = $this->pedido($offer, 1);

        $this->pagar($order, '942');

        $this->notificar('topic_chargebacks_wh', ['id' => 'CB-3']);

        $this->assertSame(OrderStatus::PagamentoConfirmado, $order->fresh()->status);
        $this->assertSame('942', $order->fresh()->mercado_pago_payment_id);
    }

    // ─── F — tópico desconhecido ────────────────────────────────────────────

    public function test_f_topico_desconhecido_e_registrado_e_ignorado(): void
    {
        $offer = $this->oferta();
        $order = $this->pedido($offer, 1);

        Log::spy();

        $this->postJson(route('mercado-pago.webhook'), [
            'type' => 'merchant_order', 'data' => ['id' => '555'],
        ])->assertOk()->assertJson(['ok' => true]);

        Log::shouldHaveReceived('info')
            ->once()
            ->withArgs(function (string $mensagem, array $contexto) {
                return $mensagem === 'mercado_pago.webhook.ignorado'
                    && $contexto['topic'] === 'merchant_order'
                    && $contexto['data_id'] === '555'
                    // Metadado, nunca payload.
                    && ! array_key_exists('payload', $contexto);
            });

        $this->assertSame(OrderStatus::AguardandoPagamento, $order->fresh()->status);
    }

    // ─── G — duplicidade ────────────────────────────────────────────────────

    public function test_g_webhook_repetido_nao_duplica_efeito(): void
    {
        $offer = $this->oferta();
        $order = $this->pedido($offer, 2);

        $this->pagar($order, '951');
        $this->notificar('payment', ['id' => '951']);
        $this->notificar('payment', ['id' => '951']);

        $order->refresh();

        $this->assertSame(8, $offer->fresh()->stock_quantity);
        $this->assertSame(0, $offer->fresh()->reserved_quantity);
        $this->assertSame(OrderSplitStatus::Confirmado, $order->splits->first()->fresh()->status);
    }

    public function test_g_chargeback_repetido_nao_duplica_registro(): void
    {
        $offer = $this->oferta();
        $order = $this->pedido($offer, 1);

        $this->pagar($order, '961');

        $this->notificar('topic_chargebacks_wh', ['id' => 'CB-4', 'payment_id' => '961']);
        $this->notificar('topic_chargebacks_wh', ['id' => 'CB-4', 'payment_id' => '961']);

        $chaves = array_keys($order->fresh()->payment_payload);

        $this->assertSame(
            1,
            count(array_filter($chaves, fn ($c) => str_starts_with($c, 'reversao_charged_back_'))),
        );
    }

    // ─── Cancelamento antes do pagamento segue como era ─────────────────────

    public function test_cancelamento_pelo_gateway_devolve_a_reserva(): void
    {
        // V-1: antes da 01F-B o gateway escrevia `Cancelado` direto e as
        // unidades ficavam comprometidas para sempre — o lojista nem conseguia
        // excluir a oferta (D-FIN-24) nem reduzir o estoque abaixo dela.
        $offer = $this->oferta();
        $order = $this->pedido($offer, 3);

        $this->assertSame(3, $offer->fresh()->reserved_quantity);

        $this->pagamentoNoGateway('971', 'cancelled', $order);
        $this->notificar('payment', ['id' => '971']);

        $order->refresh();

        $this->assertSame(OrderStatus::Cancelado, $order->status);
        $this->assertNotNull($order->stock_released_at);
        $this->assertSame(0, $offer->fresh()->reserved_quantity);
        $this->assertSame(10, $offer->fresh()->disponivel());
    }

    public function test_cancelamento_de_pedido_ja_encerrado_e_registrado_sem_erro(): void
    {
        $offer = $this->oferta();
        $order = $this->pedido($offer, 1);
        Order::whereKey($order->getKey())->update(['status' => 'concluido']);

        $this->pagamentoNoGateway('972', 'cancelled', $order);
        $this->notificar('payment', ['id' => '972']);

        $order->refresh();

        $this->assertSame(OrderStatus::Concluido, $order->status);
        $this->assertArrayHasKey('cancelamento_recusado_972', $order->payment_payload);
    }

    public function test_cancelled_sobre_pedido_pago_e_tratado_como_reversao(): void
    {
        // O gateway usa `cancelled` para os dois casos. Cancelar o que nunca
        // foi pago encerra uma intenção; "cancelar" o que já foi pago é
        // reversão financeira, e não pode destruir a venda por este caminho.
        $offer = $this->oferta();
        $order = $this->pedido($offer, 1);

        $this->pagar($order, '981');
        $this->pagamentoNoGateway('981', 'cancelled', $order);
        $this->notificar('payment', ['id' => '981']);

        $order->refresh();

        $this->assertSame(OrderStatus::PagamentoConfirmado, $order->status);
        $this->assertArrayHasKey('reversao_cancelled_981', $order->payment_payload);
    }
}
