<?php

namespace Tests\Feature;

use App\Actions\Orders\CancelOrder;
use App\Actions\Payments\ConfirmOrderPayment;
use App\DTO\PaymentConfirmation;
use App\Enums\OrderSplitStatus;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Events\OrderSplitConfirmed;
use App\Exceptions\SplitDePedidoNaoPago;
use App\Livewire\Lojista\Pedidos\PedidoIndex;
use App\Models\Ava\AvaCourse;
use App\Models\Ava\AvaEnrollment;
use App\Models\Expositor;
use App\Models\Order;
use App\Models\OrderSplit;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * FIN-SEC-01G.1 — repasse só se confirma depois que o dinheiro entra.
 *
 * Duas superfícies confirmam repasse à mão: o botão do painel do lojista e
 * `PATCH /api/v1/lojista/pedidos/{split}/confirmar-pagamento`. Nenhuma das duas
 * perguntava em que estado estava o **pedido**.
 *
 * ## A guarda errada, e a certa
 *
 * A FIN-SEC-01G fechou o buraco pela metade, usando `ehTerminal()`: barrou
 * `Cancelado`, `Expirado` e `Estornado`, e deixou passar `AguardandoPagamento`
 * — o caso mais comum de todos, um pedido que simplesmente ainda não foi pago.
 * "Não encerrado" nunca quis dizer "pago".
 *
 * A pergunta certa é sobre autoridade financeira:
 * `OrderStatus::temPagamentoConfirmado()`.
 *
 * ## Por que isso não tira função de ninguém
 *
 * `ConfirmOrderPayment` confirma **todos** os splits pendentes na mesma
 * transação em que o pedido vira pago. Depois dela não sobra split pendente
 * para confirmar à mão. O caminho manual só conseguia produzir estado inválido:
 * repasse devido sem dinheiro, e — no caso digital — matrícula em curso pago.
 */
class ConfirmacaoManualDoLojistaTest extends TestCase
{
    use RefreshDatabase;

    private static int $counter = 0;

    /** @return array{0: Order, 1: User} */
    private function cenario(bool $digital = false): array
    {
        self::$counter++;

        $lojista = User::factory()->create(['role' => UserRole::Lojista, 'is_active' => true]);

        $expositor = Expositor::create([
            'user_id' => $lojista->id,
            'name' => 'Loja Manual '.self::$counter,
            'slug' => 'manual-loja-'.self::$counter,
            'is_active' => true,
        ]);

        $product = Product::factory()->create([
            'expositor_id' => $expositor->id,
            'item_type' => $digital ? 'servico' : 'produto',
            'name' => 'Item Manual '.self::$counter,
            'slug' => 'manual-item-'.self::$counter,
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

        $this->actingAs(User::factory()->create());
        app(CartService::class)->add($offer->fresh(), 1);

        $order = app(OrderService::class)->createFromCart([
            'customer_name' => 'Cliente',
            'customer_whatsapp' => '(11)99999-0000',
            'customer_email' => 'cliente@teste.com',
            'delivery_type' => 'retirada',
            'address_cep' => '01001000', 'address_rua' => 'Rua', 'address_numero' => '1',
            'address_bairro' => 'Centro', 'address_cidade' => 'Sao Paulo', 'address_estado' => 'SP',
            'shipping_total' => 0,
        ], app(CartService::class));

        return [$order, $lojista];
    }

    private function forcarEstado(Order $order, OrderStatus $status): void
    {
        Order::whereKey($order->getKey())->update([
            'status' => $status->value,
            'paid_at' => $status->temPagamentoConfirmado() ? now() : null,
        ]);
    }

    // ─── O que a guarda impede ───────────────────────────────────────────────

    /** @return array<string, array{0: OrderStatus}> */
    public static function estadosSemAutoridadeFinanceira(): array
    {
        return [
            // O achado da G.1: não encerrado, e mesmo assim sem dinheiro.
            'aguardando pagamento' => [OrderStatus::AguardandoPagamento],
            'cancelado' => [OrderStatus::Cancelado],
            'expirado' => [OrderStatus::Expirado],
            'estornado' => [OrderStatus::Estornado],
        ];
    }

    #[DataProvider('estadosSemAutoridadeFinanceira')]
    public function test_split_nao_confirma_sem_pagamento_do_pedido(OrderStatus $status): void
    {
        [$order] = $this->cenario();

        $this->forcarEstado($order, $status);

        $split = $order->splits->first()->fresh();
        $this->assertSame(OrderSplitStatus::Pendente, $split->status);

        try {
            $split->confirmar();
            $this->fail("Confirmação deveria ter sido recusada em {$status->value}.");
        } catch (SplitDePedidoNaoPago) {
            // esperado
        }

        $this->assertSame(OrderSplitStatus::Pendente, $split->fresh()->status);
        $this->assertNull($split->fresh()->confirmed_at);
    }

    public function test_recusa_acontece_antes_do_evento(): void
    {
        // A correção precisa impedir o evento **na origem**: o listener do AVA
        // não é consultado sobre validade, ele só executa.
        [$order] = $this->cenario();

        Event::fake([OrderSplitConfirmed::class]);

        try {
            $order->splits->first()->fresh()->confirmar();
        } catch (SplitDePedidoNaoPago) {
            // esperado
        }

        Event::assertNotDispatched(OrderSplitConfirmed::class);
    }

    public function test_pedido_sem_pagamento_nao_libera_curso(): void
    {
        // O dano real do achado: acesso a produto digital sem pagamento.
        [$order] = $this->cenario(digital: true);

        $this->assertSame(OrderStatus::AguardandoPagamento, $order->fresh()->status);

        try {
            $order->splits->first()->fresh()->confirmar();
        } catch (SplitDePedidoNaoPago) {
            // esperado
        }

        $this->assertSame(0, AvaEnrollment::count());
    }

    public function test_pedido_cancelado_nao_libera_curso(): void
    {
        [$order] = $this->cenario(digital: true);

        app(CancelOrder::class)($order);

        try {
            $order->splits->first()->fresh()->confirmar();
        } catch (SplitDePedidoNaoPago) {
            // esperado
        }

        $this->assertSame(0, AvaEnrollment::count());
    }

    // ─── As duas superfícies reais ───────────────────────────────────────────

    public function test_a_rota_da_api_recusa_pedido_nao_pago_com_409(): void
    {
        [$order, $lojista] = $this->cenario();

        $split = $order->splits->first();

        $this->actingAs($lojista, 'sanctum')
            ->patchJson("/api/v1/lojista/pedidos/{$split->id}/confirmar-pagamento")
            ->assertStatus(409);

        $this->assertSame(OrderSplitStatus::Pendente, $split->fresh()->status);
        $this->assertSame(0, AvaEnrollment::count());
    }

    public function test_o_painel_do_lojista_avisa_em_vez_de_estourar(): void
    {
        [$order, $lojista] = $this->cenario();

        $split = $order->splits->first();

        $this->actingAs($lojista);

        // Se a recusa escapasse, `call()` propagaria a exceção e o teste
        // quebraria aqui — é isso que prova que o painel trata em vez de
        // estourar um 500 na cara do lojista.
        Livewire::test(PedidoIndex::class)
            ->call('confirmar', $split->id)
            ->assertHasNoErrors()
            ->assertOk();

        $this->assertSame(OrderSplitStatus::Pendente, $split->fresh()->status);
        $this->assertNull($split->fresh()->confirmed_at);
    }

    public function test_a_mensagem_nomeia_o_estado_real_do_pedido(): void
    {
        // Dizer "encerrado" para um pedido aguardando pagamento mentiria sobre
        // o motivo da recusa — e o motivo é acionável.
        [$order] = $this->cenario();

        try {
            $order->splits->first()->fresh()->confirmar();
            $this->fail('Deveria ter recusado.');
        } catch (SplitDePedidoNaoPago $recusada) {
            $this->assertStringContainsString('Aguardando Pagamento', $recusada->mensagem());
            $this->assertStringNotContainsString('encerrado', $recusada->mensagem());
        }
    }

    // ─── O que continua funcionando ──────────────────────────────────────────

    /** @return array<string, array{0: OrderStatus}> */
    public static function estadosComAutoridadeFinanceira(): array
    {
        return [
            'pagamento confirmado' => [OrderStatus::PagamentoConfirmado],
            'concluido' => [OrderStatus::Concluido],
        ];
    }

    #[DataProvider('estadosComAutoridadeFinanceira')]
    public function test_split_pendente_de_pedido_pago_ainda_confirma(OrderStatus $status): void
    {
        // Estado que a arquitetura atual não produz — `ConfirmOrderPayment`
        // confirma todos os splits na mesma transação. Permitido porque não
        // antecipa pagamento nenhum: o dinheiro já entrou.
        [$order] = $this->cenario();

        $this->forcarEstado($order, $status);
        OrderSplit::where('order_id', $order->id)->update(['status' => OrderSplitStatus::Pendente->value]);

        $split = $order->splits->first()->fresh();
        $split->confirmar();

        $this->assertSame(OrderSplitStatus::Confirmado, $split->fresh()->status);
        $this->assertNotNull($split->fresh()->confirmed_at);
    }

    public function test_o_fluxo_automatico_de_pagamento_continua_confirmando_tudo(): void
    {
        // A regressão que importa: a guarda não pode atrapalhar quem tem
        // autoridade. `ConfirmOrderPayment` transiciona o pedido **antes** de
        // confirmar os splits, então a guarda encontra o estado já pago.
        [$order] = $this->cenario(digital: true);

        app(ConfirmOrderPayment::class)($order, new PaymentConfirmation(
            provider: 'mercado_pago',
            externalPaymentId: '5001',
            amount: (float) $order->total_amount,
            paidAt: now(),
        ));

        $order->refresh();

        $this->assertSame(OrderStatus::PagamentoConfirmado, $order->status);
        $this->assertSame(OrderSplitStatus::Confirmado, $order->splits->first()->fresh()->status);
        $this->assertSame(1, AvaEnrollment::count());
    }

    // ─── A regra, direto no enum ─────────────────────────────────────────────

    public function test_autoridade_financeira_e_diferente_de_nao_terminal(): void
    {
        // O erro da G, em uma asserção: `AguardandoPagamento` não é terminal e
        // mesmo assim não tem autoridade financeira nenhuma.
        $this->assertFalse(OrderStatus::AguardandoPagamento->ehTerminal());
        $this->assertFalse(OrderStatus::AguardandoPagamento->temPagamentoConfirmado());

        $this->assertTrue(OrderStatus::PagamentoConfirmado->temPagamentoConfirmado());
        $this->assertTrue(OrderStatus::Concluido->temPagamentoConfirmado());

        // `Estornado` tem `paid_at`, e mesmo assim não sustenta repasse: o
        // dinheiro voltou.
        $this->assertFalse(OrderStatus::Estornado->temPagamentoConfirmado());
        $this->assertFalse(OrderStatus::Cancelado->temPagamentoConfirmado());
        $this->assertFalse(OrderStatus::Expirado->temPagamentoConfirmado());
    }
}
