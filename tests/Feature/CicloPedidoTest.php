<?php

namespace Tests\Feature;

use App\Actions\Orders\CancelOrder;
use App\Actions\Orders\CompleteOrder;
use App\Enums\OrderSplitStatus;
use App\Enums\OrderStatus;
use App\Enums\ShippingStatus;
use App\Enums\UserRole;
use App\Exceptions\SplitRevertidoNaoReconfirma;
use App\Exceptions\TransicaoDePedidoInvalida;
use App\Jobs\TrackShipmentsJob;
use App\Livewire\Admin\Pedidos\PedidoIndex as AdminPedidoIndex;
use App\Livewire\Cliente\Pedidos\PedidoIndex as ClientePedidoIndex;
use App\Models\Expositor;
use App\Models\Order;
use App\Models\OrderShipping;
use App\Models\OrderSplit;
use App\Models\Product;
use App\Models\ProductOffer;
use App\Models\User;
use App\Services\CartService;
use App\Services\OrderService;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use Tests\TestCase;

/**
 * FIN-SEC-01F-B — o pedido só anda pelos caminhos que a matriz permite.
 *
 * Antes desta subfase o estado do pedido era escrito de cinco lugares, um deles
 * um `<select>` do painel administrativo que aceitava qualquer origem e
 * qualquer destino. Dava para marcar como pago o que ninguém pagou, concluir o
 * que estava cancelado, e cancelar sem devolver o estoque reservado.
 *
 * Agora a matriz vive em `OrderStatus::destinosPermitidos()` e as transições
 * passam por actions que travam o pedido antes de decidir.
 */
class CicloPedidoTest extends TestCase
{
    use RefreshDatabase;

    private static int $counter = 0;

    private function oferta(): ProductOffer
    {
        self::$counter++;

        $lojista = User::factory()->create(['role' => UserRole::Lojista, 'is_active' => true]);

        $expositor = Expositor::create([
            'user_id' => $lojista->id,
            'name' => 'Loja Ciclo '.self::$counter,
            'slug' => 'ciclo-b-loja-'.self::$counter,
            'is_active' => true,
        ]);

        $product = Product::factory()->create([
            'expositor_id' => $expositor->id,
            'item_type' => 'produto',
            'name' => 'Item Ciclo '.self::$counter,
            'slug' => 'ciclo-b-item-'.self::$counter,
            'price' => 100,
        ]);

        $offer = $product->offers()->first();
        $offer->update(['has_stock' => true, 'stock_quantity' => 10]);

        return $offer->fresh();
    }

    private function pedido(ProductOffer $offer, int $qty = 2, ?User $cliente = null): Order
    {
        $this->actingAs($cliente ?? User::factory()->create());

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

    /** Coloca o pedido num estado terminal sem passar por action. */
    private function forcarEstado(Order $order, OrderStatus $status): Order
    {
        Order::whereKey($order->getKey())->update(['status' => $status->value]);

        return $order->fresh();
    }

    // ─── Cancelamento ────────────────────────────────────────────────────────

    public function test_cancelar_devolve_a_reserva_na_mesma_transacao(): void
    {
        $offer = $this->oferta();
        $order = $this->pedido($offer, 3);

        $this->assertSame(3, $offer->fresh()->reserved_quantity);

        app(CancelOrder::class)($order);

        $order->refresh();

        $this->assertSame(OrderStatus::Cancelado, $order->status);
        $this->assertNotNull($order->stock_released_at);
        $this->assertSame(0, $offer->fresh()->reserved_quantity);
        $this->assertSame(10, $offer->fresh()->stock_quantity);
        $this->assertSame(10, $offer->fresh()->disponivel());
    }

    public function test_cancelar_duas_vezes_nao_devolve_duas_vezes(): void
    {
        $offer = $this->oferta();
        $order = $this->pedido($offer, 3);

        app(CancelOrder::class)($order);
        $liberadoEm = $order->fresh()->stock_released_at;

        app(CancelOrder::class)($order->fresh());

        $this->assertSame(0, $offer->fresh()->reserved_quantity);
        $this->assertSame(10, $offer->fresh()->stock_quantity);
        $this->assertEquals($liberadoEm, $order->fresh()->stock_released_at);
    }

    public function test_cancelar_pedido_sem_reserva_continua_funcionando(): void
    {
        // Oferta sem controle de estoque: não há o que devolver, e cancelar
        // não pode falhar por isso.
        $offer = $this->oferta();
        $offer->update(['has_stock' => false, 'stock_quantity' => null]);

        $order = $this->pedido($offer->fresh(), 2);

        app(CancelOrder::class)($order);

        $this->assertSame(OrderStatus::Cancelado, $order->fresh()->status);
    }

    public function test_pedido_pago_nao_e_cancelado_por_esta_action(): void
    {
        $offer = $this->oferta();
        $order = $this->pedido($offer, 1);
        $this->forcarEstado($order, OrderStatus::PagamentoConfirmado);

        $this->expectException(TransicaoDePedidoInvalida::class);

        app(CancelOrder::class)($order->fresh());
    }

    // ─── Superfície do cliente ───────────────────────────────────────────────

    public function test_cliente_cancela_o_proprio_pedido_pendente(): void
    {
        $cliente = User::factory()->create();
        $offer = $this->oferta();
        $order = $this->pedido($offer, 2, $cliente);

        Livewire::actingAs($cliente)
            ->test(ClientePedidoIndex::class)
            ->call('cancelar', $order->id);

        $this->assertSame(OrderStatus::Cancelado, $order->fresh()->status);
        $this->assertSame(0, $offer->fresh()->reserved_quantity);
    }

    public function test_cliente_nao_cancela_pedido_de_outro(): void
    {
        $dono = User::factory()->create();
        $intruso = User::factory()->create();

        $offer = $this->oferta();
        $order = $this->pedido($offer, 2, $dono);

        try {
            Livewire::actingAs($intruso)
                ->test(ClientePedidoIndex::class)
                ->call('cancelar', $order->id);

            $this->fail('O pedido de outro cliente não deveria ter sido alcançado.');
        } catch (ModelNotFoundException) {
            // O pedido alheio simplesmente não existe para este usuário.
        }

        // SEC-02: a posse não é presumida pelo id que chegou no request.
        $this->assertSame(OrderStatus::AguardandoPagamento, $order->fresh()->status);
        $this->assertSame(2, $offer->fresh()->reserved_quantity);
    }

    public function test_cliente_nao_cancela_pedido_ja_pago(): void
    {
        $cliente = User::factory()->create();
        $offer = $this->oferta();
        $order = $this->pedido($offer, 1, $cliente);
        $this->forcarEstado($order, OrderStatus::PagamentoConfirmado);

        Livewire::actingAs($cliente)
            ->test(ClientePedidoIndex::class)
            ->call('cancelar', $order->id)
            ->assertSee('não pode passar para');

        $this->assertSame(OrderStatus::PagamentoConfirmado, $order->fresh()->status);
    }

    // ─── Painel administrativo ───────────────────────────────────────────────

    public function test_admin_nao_escreve_status_arbitrariamente(): void
    {
        // O `updateStatus` genérico deixou de existir: o painel não fabrica
        // estado, chama a action que corresponde à operação.
        $this->assertFalse(method_exists(AdminPedidoIndex::class, 'updateStatus'));
        $this->assertTrue(method_exists(AdminPedidoIndex::class, 'cancelar'));
    }

    public function test_admin_cancela_pedido_pendente_e_devolve_estoque(): void
    {
        $this->seed(RolePermissionSeeder::class);

        $admin = User::factory()->create(['role' => UserRole::Admin, 'is_active' => true]);
        $admin->assignRole(UserRole::Admin->spatieRole());

        $offer = $this->oferta();
        $order = $this->pedido($offer, 4);

        Livewire::actingAs($admin)
            ->test(AdminPedidoIndex::class)
            ->call('cancelar', $order->id);

        $this->assertSame(OrderStatus::Cancelado, $order->fresh()->status);
        $this->assertSame(0, $offer->fresh()->reserved_quantity);
    }

    // ─── Conclusão e o job de logística ──────────────────────────────────────

    public function test_pedido_pago_com_tudo_entregue_conclui(): void
    {
        $offer = $this->oferta();
        $order = $this->pedido($offer, 1);
        $this->forcarEstado($order, OrderStatus::PagamentoConfirmado);

        $shipping = $this->envioEntregue($order);
        $this->rodarConclusaoDoJob($shipping);

        $this->assertSame(OrderStatus::Concluido, $order->fresh()->status);
    }

    /**
     * V-7: nenhum estado terminal é ressuscitado pela logística.
     */
    #[DataProvider('estadosQueNaoConcluem')]
    public function test_job_de_logistica_nao_ressuscita_estado_terminal(OrderStatus $terminal): void
    {
        $offer = $this->oferta();
        $order = $this->pedido($offer, 1);
        $this->forcarEstado($order, $terminal);

        $shipping = $this->envioEntregue($order);
        $this->rodarConclusaoDoJob($shipping);

        $this->assertSame($terminal, $order->fresh()->status);
    }

    /** @return array<string, array{0: OrderStatus}> */
    public static function estadosQueNaoConcluem(): array
    {
        return [
            'cancelado' => [OrderStatus::Cancelado],
            'expirado' => [OrderStatus::Expirado],
            'estornado' => [OrderStatus::Estornado],
        ];
    }

    #[DataProvider('estadosQueNaoConcluem')]
    public function test_completar_recusa_estado_terminal(OrderStatus $terminal): void
    {
        $order = $this->pedido($this->oferta(), 1);
        $this->forcarEstado($order, $terminal);

        $this->expectException(TransicaoDePedidoInvalida::class);

        app(CompleteOrder::class)($order->fresh());
    }

    public function test_concluido_nao_volta_atras(): void
    {
        $order = $this->pedido($this->oferta(), 1);
        $this->forcarEstado($order, OrderStatus::Concluido);

        $this->expectException(TransicaoDePedidoInvalida::class);

        app(CancelOrder::class)($order->fresh());
    }

    public function test_concluir_duas_vezes_e_idempotente(): void
    {
        $order = $this->pedido($this->oferta(), 1);
        $this->forcarEstado($order, OrderStatus::PagamentoConfirmado);

        app(CompleteOrder::class)($order->fresh());
        app(CompleteOrder::class)($order->fresh());

        $this->assertSame(OrderStatus::Concluido, $order->fresh()->status);
    }

    private function envioEntregue(Order $order): OrderShipping
    {
        $split = $order->splits->first();

        return OrderShipping::create([
            'order_id' => $order->id,
            'order_split_id' => $split->id,
            'expositor_id' => $split->expositor_id,
            'status' => ShippingStatus::Delivered,
            'shipped_at' => now()->subDays(2),
        ]);
    }

    private function rodarConclusaoDoJob(OrderShipping $shipping): void
    {
        $metodo = new ReflectionMethod(TrackShipmentsJob::class, 'checkOrderCompletion');
        $metodo->setAccessible(true);
        $metodo->invoke(new TrackShipmentsJob, $shipping->fresh());
    }

    // ─── A matriz ────────────────────────────────────────────────────────────

    #[DataProvider('transicoesProibidas')]
    public function test_a_matriz_recusa_transicoes_proibidas(OrderStatus $origem, OrderStatus $destino): void
    {
        $this->assertFalse($origem->podeIrPara($destino));
    }

    /** @return array<string, array{0: OrderStatus, 1: OrderStatus}> */
    public static function transicoesProibidas(): array
    {
        return [
            'cancelado nao confirma' => [OrderStatus::Cancelado, OrderStatus::PagamentoConfirmado],
            'expirado nao confirma' => [OrderStatus::Expirado, OrderStatus::PagamentoConfirmado],
            'estornado nao confirma' => [OrderStatus::Estornado, OrderStatus::PagamentoConfirmado],
            'concluido nao confirma' => [OrderStatus::Concluido, OrderStatus::PagamentoConfirmado],
            'cancelado nao conclui' => [OrderStatus::Cancelado, OrderStatus::Concluido],
            'expirado nao conclui' => [OrderStatus::Expirado, OrderStatus::Concluido],
            'estornado nao conclui' => [OrderStatus::Estornado, OrderStatus::Concluido],
            // Estornar exige ter havido dinheiro. Nenhum destes teve.
            'aguardando nao estorna' => [OrderStatus::AguardandoPagamento, OrderStatus::Estornado],
            'cancelado nao estorna' => [OrderStatus::Cancelado, OrderStatus::Estornado],
            'expirado nao estorna' => [OrderStatus::Expirado, OrderStatus::Estornado],
        ];
    }

    public function test_a_matriz_permite_o_ciclo_legitimo(): void
    {
        $this->assertTrue(OrderStatus::AguardandoPagamento->podeIrPara(OrderStatus::PagamentoConfirmado));
        $this->assertTrue(OrderStatus::AguardandoPagamento->podeIrPara(OrderStatus::Cancelado));
        $this->assertTrue(OrderStatus::AguardandoPagamento->podeIrPara(OrderStatus::Expirado));
        $this->assertTrue(OrderStatus::PagamentoConfirmado->podeIrPara(OrderStatus::Concluido));
        $this->assertTrue(OrderStatus::PagamentoConfirmado->podeIrPara(OrderStatus::Estornado));

        // FIN-SEC-01F-D: uma compra entregue pode ser estornada depois. A
        // evidência logística disso não vive aqui — vive em
        // `order_shippings.delivered_at` —, e por isso a transição não apaga
        // nada. Ver a nota em `OrderStatus::destinosPermitidos()`.
        $this->assertTrue(OrderStatus::Concluido->podeIrPara(OrderStatus::Estornado));
        $this->assertFalse(OrderStatus::Concluido->ehTerminal());

        foreach ([OrderStatus::Cancelado, OrderStatus::Expirado, OrderStatus::Estornado] as $terminal) {
            $this->assertTrue($terminal->ehTerminal());
            $this->assertSame([], $terminal->destinosPermitidos());
        }
    }

    // ─── Split revertido ─────────────────────────────────────────────────────

    public function test_split_revertido_nao_volta_a_confirmado(): void
    {
        $order = $this->pedido($this->oferta(), 1);
        $split = $order->splits->first();

        OrderSplit::whereKey($split->id)->update(['status' => OrderSplitStatus::Revertido->value]);

        $this->expectException(SplitRevertidoNaoReconfirma::class);

        $split->fresh()->confirmar();
    }

    public function test_a_api_do_lojista_nao_reconfirma_split_revertido(): void
    {
        $order = $this->pedido($this->oferta(), 1);
        $split = $order->splits->first();

        OrderSplit::whereKey($split->id)->update(['status' => OrderSplitStatus::Revertido->value]);

        $this->actingAs($split->expositor->user, 'sanctum');

        $this->patchJson("/api/v1/lojista/pedidos/{$split->id}/confirmar-pagamento")
            ->assertStatus(409);

        $this->assertSame(OrderSplitStatus::Revertido, $split->fresh()->status);
    }

    public function test_split_pendente_continua_confirmando(): void
    {
        $order = $this->pedido($this->oferta(), 1);
        $split = $order->splits->first();

        // Sobre pedido pago: desde a FIN-SEC-01G.1 a confirmacao de repasse
        // exige autoridade financeira, e nao apenas pedido nao encerrado.
        Order::whereKey($order->getKey())->update([
            'status' => OrderStatus::PagamentoConfirmado->value,
            'paid_at' => now(),
        ]);

        $split->fresh()->confirmar();

        $this->assertSame(OrderSplitStatus::Confirmado, $split->fresh()->status);
    }

    public function test_as_views_distinguem_os_tres_estados_do_split(): void
    {
        // Antes, qualquer coisa que não fosse `confirmado` era pintada como
        // pendente: um split revertido apareceria amarelo, como se ainda
        // houvesse repasse a fazer.
        $badges = array_map(fn (OrderSplitStatus $s) => $s->badge(), OrderSplitStatus::cases());

        $this->assertCount(3, $badges);
        $this->assertCount(3, array_unique($badges));
        $this->assertCount(3, array_unique(array_map(fn ($s) => $s->label(), OrderSplitStatus::cases())));
    }
}
