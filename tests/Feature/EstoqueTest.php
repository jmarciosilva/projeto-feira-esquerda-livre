<?php

namespace Tests\Feature;

use App\Actions\Stock\ReleaseOrderStock;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Exceptions\EstoqueInsuficiente;
use App\Livewire\Lojista\Produtos\ProdutoForm;
use App\Livewire\Lojista\Produtos\ProdutoIndex;
use App\Models\Ava\AvaCourse;
use App\Models\Ava\AvaEnrollment;
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
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * FIN-SEC-01E — cada unidade física pertence a um pedido por vez.
 *
 * A auditoria desta fase reproduziu o estado anterior: dois clientes compravam
 * a mesma última peça **sem sequer precisar de concorrência**, porque nada
 * validava e nada comprometia estoque. Os testes abaixo nasceram descrevendo
 * aquele comportamento e foram invertidos junto com a implementação — é isso
 * que os torna prova de que a lacuna existia e foi fechada.
 *
 * O modelo é físico + comprometido:
 *
 *     disponível = stock_quantity − reserved_quantity
 *
 * O checkout compromete, o pagamento consome, o cancelamento devolve.
 */
class EstoqueTest extends TestCase
{
    use RefreshDatabase;

    private static int $counter = 0;

    private function expositor(): Expositor
    {
        self::$counter++;

        $lojista = User::factory()->create(['role' => UserRole::Lojista, 'is_active' => true]);

        return Expositor::create([
            'user_id' => $lojista->id,
            'name' => 'Loja Estoque '.self::$counter,
            'slug' => 'estoque-loja-'.self::$counter,
            'is_active' => true,
        ]);
    }

    /** @param array<string, mixed> $offerAttrs */
    private function oferta(array $offerAttrs = [], bool $digital = false): ProductOffer
    {
        self::$counter++;
        $expositor = $this->expositor();

        $product = Product::factory()->create([
            'expositor_id' => $expositor->id,
            'item_type' => $digital ? 'servico' : 'produto',
            'name' => 'Item '.self::$counter,
            'slug' => 'estoque-item-'.self::$counter,
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
        $offer->update($offerAttrs + ['has_stock' => true, 'stock_quantity' => 10]);

        return $offer->fresh();
    }

    /** @param array<int, array{0: ProductOffer, 1: int}> $itens */
    private function pedidoCom(array $itens, ?User $cliente = null): Order
    {
        $cliente ??= User::factory()->create();
        $this->actingAs($cliente);

        $cart = app(CartService::class);

        foreach ($itens as [$offer, $qty]) {
            $cart->add($offer, $qty);
        }

        return app(OrderService::class)->createFromCart([
            'customer_name' => 'Cliente',
            'customer_whatsapp' => '(11)99999-0000',
            'delivery_type' => 'retirada',
            'address_cep' => '01001000', 'address_rua' => 'Rua', 'address_numero' => '1',
            'address_bairro' => 'Centro', 'address_cidade' => 'Sao Paulo', 'address_estado' => 'SP',
            'shipping_total' => 0,
        ], app(CartService::class));
    }

    private function pedidoDe(ProductOffer $offer, int $qty, ?User $cliente = null): Order
    {
        return $this->pedidoCom([[$offer, $qty]], $cliente);
    }

    /**
     * Transforma um pedido recem-criado num pedido "legado": sem marca de
     * reserva e sem nada comprometido nas ofertas.
     *
     * A escrita e por query direta de proposito — `$offer->update()` sobre uma
     * instancia carregada antes da reserva nao emite UPDATE nenhum, porque o
     * atributo em memoria ja vale zero e o Eloquent o considera limpo.
     */
    private function tornarLegacy(Order $order): void
    {
        Order::whereKey($order->getKey())->update(['stock_reserved_at' => null]);

        ProductOffer::whereIn('id', $order->items()->pluck('product_offer_id')->filter())
            ->update(['reserved_quantity' => 0]);
    }

    private function pagar(Order $order, string $paymentId = '999'): TestResponse
    {
        SiteSetting::instance()->update([
            'mercado_pago_ativo' => true,
            'mercado_pago_access_token' => 'TEST_TOKEN',
            'mercado_pago_sandbox' => true,
        ]);

        Http::fake([
            'api.mercadopago.com/v1/payments/'.$paymentId => Http::response([
                'id' => (int) $paymentId,
                'status' => 'approved',
                'external_reference' => $order->reference,
                'transaction_amount' => (float) $order->total_amount,
                'date_approved' => '2026-06-29T12:00:00.000-03:00',
            ]),
        ]);

        return $this->postJson(route('mercado-pago.webhook'), [
            'type' => 'payment', 'data' => ['id' => $paymentId],
        ]);
    }

    // ─── Reserva no checkout ────────────────────────────────────────────────

    public function test_o_pedido_compromete_o_estoque_sem_baixar_o_fisico(): void
    {
        $offer = $this->oferta(['stock_quantity' => 5]);

        $order = $this->pedidoDe($offer, 3);

        $offer->refresh();
        $this->assertSame(5, $offer->stock_quantity);
        $this->assertSame(3, $offer->reserved_quantity);
        $this->assertSame(2, $offer->disponivel());
        $this->assertNotNull($order->fresh()->stock_reserved_at);
    }

    public function test_oferta_esgotada_nao_vira_pedido(): void
    {
        $offer = $this->oferta(['stock_quantity' => 0]);

        $this->expectException(EstoqueInsuficiente::class);

        try {
            $this->pedidoDe($offer, 1);
        } finally {
            $this->assertSame(0, Order::count());
            $this->assertSame(0, $offer->fresh()->reserved_quantity);
        }
    }

    public function test_pedido_maior_que_o_disponivel_e_recusado(): void
    {
        $offer = $this->oferta(['stock_quantity' => 1]);

        $this->expectException(EstoqueInsuficiente::class);

        try {
            $this->pedidoDe($offer, 2);
        } finally {
            $this->assertSame(0, Order::count());
            $this->assertSame(0, $offer->fresh()->reserved_quantity);
        }
    }

    public function test_dois_clientes_nao_levam_a_mesma_ultima_unidade(): void
    {
        $offer = $this->oferta(['stock_quantity' => 1]);

        $this->pedidoDe($offer, 1);

        // O segundo cliente chega e a peça já tem dono.
        try {
            $this->pedidoDe($offer, 1);
            $this->fail('O segundo pedido deveria ter sido recusado por falta de estoque.');
        } catch (EstoqueInsuficiente) {
            // esperado
        }

        $this->assertSame(1, Order::count());
        $this->assertSame(1, $offer->fresh()->reserved_quantity);
        $this->assertSame(0, $offer->fresh()->disponivel());
    }

    public function test_pedido_multi_item_falha_inteiro_quando_um_item_nao_cabe(): void
    {
        $comEstoque = $this->oferta(['stock_quantity' => 5]);
        $semEstoque = $this->oferta(['stock_quantity' => 1]);

        try {
            $this->pedidoCom([[$comEstoque, 2], [$semEstoque, 2]]);
            $this->fail('O pedido deveria ter falhado por causa do segundo item.');
        } catch (EstoqueInsuficiente) {
            // esperado
        }

        // Nada pela metade: o primeiro item não fica comprometido.
        $this->assertSame(0, Order::count());
        $this->assertSame(0, $comEstoque->fresh()->reserved_quantity);
        $this->assertSame(0, $semEstoque->fresh()->reserved_quantity);
    }

    // ─── Consumo no pagamento ───────────────────────────────────────────────

    public function test_pagamento_converte_reserva_em_baixa(): void
    {
        $offer = $this->oferta(['stock_quantity' => 5]);
        $order = $this->pedidoDe($offer, 3);

        $this->pagar($order)->assertOk();

        $offer->refresh();
        $this->assertSame(2, $offer->stock_quantity);
        $this->assertSame(0, $offer->reserved_quantity);
        $this->assertSame(2, $offer->disponivel());
        $this->assertNotNull($order->fresh()->stock_consumed_at);
    }

    public function test_webhook_repetido_nao_baixa_estoque_duas_vezes(): void
    {
        $offer = $this->oferta(['stock_quantity' => 5]);
        $order = $this->pedidoDe($offer, 3);

        $this->pagar($order)->assertOk();
        $this->pagar($order)->assertOk();
        $this->pagar($order)->assertOk();

        $offer->refresh();
        $this->assertSame(2, $offer->stock_quantity);
        $this->assertSame(0, $offer->reserved_quantity);
    }

    // ─── Pedidos anteriores à fase, sem reserva ─────────────────────────────

    public function test_pedido_legacy_consome_o_estoque_disponivel_ao_pagar(): void
    {
        $offer = $this->oferta(['stock_quantity' => 5]);
        $order = $this->pedidoDe($offer, 2);

        // Simula um pedido criado antes desta fase: a marca de reserva não
        // existe, e o comprometido volta a zero.
        $this->tornarLegacy($order);

        $this->pagar($order)->assertOk();

        $offer->refresh();
        $this->assertSame(3, $offer->stock_quantity);
        $this->assertSame(0, $offer->reserved_quantity);
        $this->assertSame(OrderStatus::PagamentoConfirmado, $order->fresh()->status);
    }

    public function test_pedido_legacy_sem_estoque_nao_e_confirmado(): void
    {
        $offer = $this->oferta(['stock_quantity' => 5]);
        $order = $this->pedidoDe($offer, 2);

        $this->tornarLegacy($order);
        // O estoque acabou entre a criação do pedido e o pagamento.
        ProductOffer::whereKey($offer->id)->update(['stock_quantity' => 0]);

        $this->pagar($order);

        // Falha fechada: pagamento recebido pelo gateway, pedido não confirmado
        // comercialmente, e nenhuma baixa parcial.
        $order->refresh();
        $this->assertNotSame(OrderStatus::PagamentoConfirmado, $order->status);
        $this->assertNull($order->paid_at);
        $this->assertNull($order->stock_consumed_at);
        $this->assertSame(0, $offer->fresh()->stock_quantity);
        $this->assertSame('pendente', $order->splits->first()->status->value);
    }

    public function test_pedido_legacy_multi_item_falha_inteiro(): void
    {
        $ofertaA = $this->oferta(['stock_quantity' => 5]);
        $ofertaB = $this->oferta(['stock_quantity' => 5]);
        $order = $this->pedidoCom([[$ofertaA, 1], [$ofertaB, 1]]);

        $this->tornarLegacy($order);
        ProductOffer::whereKey($ofertaB->id)->update(['stock_quantity' => 0]);

        $this->pagar($order);

        $this->assertNotSame(OrderStatus::PagamentoConfirmado, $order->fresh()->status);
        $this->assertSame(5, $ofertaA->fresh()->stock_quantity);
        $this->assertSame(0, $ofertaB->fresh()->stock_quantity);
    }

    // ─── Liberação ──────────────────────────────────────────────────────────

    public function test_liberar_devolve_o_comprometido_e_e_idempotente(): void
    {
        $offer = $this->oferta(['stock_quantity' => 5]);
        $order = $this->pedidoDe($offer, 3);

        $this->assertSame(3, $offer->fresh()->reserved_quantity);

        app(ReleaseOrderStock::class)($order);
        app(ReleaseOrderStock::class)($order);

        $offer->refresh();
        $this->assertSame(5, $offer->stock_quantity);
        $this->assertSame(0, $offer->reserved_quantity);
        $this->assertSame(5, $offer->disponivel());
        $this->assertNotNull($order->fresh()->stock_released_at);
    }

    public function test_liberar_nao_devolve_estoque_ja_consumido(): void
    {
        $offer = $this->oferta(['stock_quantity' => 5]);
        $order = $this->pedidoDe($offer, 3);
        $this->pagar($order)->assertOk();

        app(ReleaseOrderStock::class)($order->fresh());

        // O que saiu da prateleira não volta por um cancelamento tardio: repor
        // é decisão de negócio, na 01F.
        $offer->refresh();
        $this->assertSame(2, $offer->stock_quantity);
        $this->assertSame(0, $offer->reserved_quantity);
    }

    // ─── Ilimitado e digital não participam ─────────────────────────────────

    /**
     * @return array<string, array{0: bool, 1: int|null}>
     */
    public static function ofertasSemControle(): array
    {
        return [
            'nao controla / sem quantidade' => [false, null],
            'nao controla / zero' => [false, 0],
            'controla / sem quantidade' => [true, null],
        ];
    }

    #[DataProvider('ofertasSemControle')]
    public function test_oferta_sem_controle_de_estoque_vende_sem_reservar(bool $controla, ?int $quantidade): void
    {
        $offer = $this->oferta(['has_stock' => $controla, 'stock_quantity' => $quantidade]);

        $order = $this->pedidoDe($offer, 99);

        $this->assertNotNull($order->id);
        $this->assertSame(0, $offer->fresh()->reserved_quantity);
        $this->assertNull($offer->fresh()->disponivel());
    }

    public function test_curso_digital_com_estoque_zero_continua_sendo_vendido(): void
    {
        $offer = $this->oferta(['has_stock' => true, 'stock_quantity' => 0], digital: true);
        $cliente = User::factory()->create();

        $order = $this->pedidoDe($offer, 1, $cliente);
        $this->pagar($order, '888')->assertOk();

        // Um curso vendido dez vezes continua sendo o mesmo curso: item digital
        // não disputa unidade física, mesmo com a coluna de estoque zerada.
        $this->assertSame(1, AvaEnrollment::where('user_id', $cliente->id)->count());
        $this->assertSame(0, $offer->fresh()->reserved_quantity);
        $this->assertSame(0, $offer->fresh()->stock_quantity);
    }

    public function test_pedido_misto_reserva_apenas_o_item_fisico(): void
    {
        $fisico = $this->oferta(['stock_quantity' => 1]);
        $digital = $this->oferta(['has_stock' => true, 'stock_quantity' => 0], digital: true);
        $cliente = User::factory()->create();

        $order = $this->pedidoCom([[$fisico, 1], [$digital, 1]], $cliente);

        $this->assertSame(1, $fisico->fresh()->reserved_quantity);
        $this->assertSame(0, $digital->fresh()->reserved_quantity);

        $this->pagar($order)->assertOk();

        $this->assertSame(0, $fisico->fresh()->stock_quantity);
        $this->assertSame(0, $fisico->fresh()->reserved_quantity);
        $this->assertSame(1, AvaEnrollment::where('user_id', $cliente->id)->count());
    }

    // ─── FIN-SEC-01E.1: desligar o controle não órfã a reserva ──────────────

    public function test_lojista_nao_apaga_a_quantidade_com_reserva_ativa(): void
    {
        // A 01E fechou "baixar o número". Faltava "dizer que não há número":
        // com `stock_quantity` nulo, `controlaEstoque()` passa a responder que
        // a oferta não participa do estoque, e o comprometido ficava órfão —
        // sem ninguém para devolvê-lo, e prendendo a oferta por D-FIN-24.
        $offer = $this->oferta(['stock_quantity' => 10]);
        $this->pedidoDe($offer, 3);

        Sanctum::actingAs($offer->expositor->user);

        $this->putJson("/api/v1/lojista/produtos/{$offer->product_id}", [
            'item_type' => 'produto',
            'name' => $offer->product->name,
            'price' => 100,
            'has_stock' => true,
            'stock_quantity' => null,
        ])->assertStatus(422)->assertJsonValidationErrors('stock_quantity');

        $offer->refresh();

        $this->assertSame(10, $offer->stock_quantity);
        $this->assertSame(3, $offer->reserved_quantity);
        $this->assertSame(7, $offer->disponivel());
    }

    public function test_lojista_nao_desliga_o_controle_com_reserva_ativa(): void
    {
        $offer = $this->oferta(['stock_quantity' => 10]);
        $this->pedidoDe($offer, 2);

        Sanctum::actingAs($offer->expositor->user);

        $this->putJson("/api/v1/lojista/produtos/{$offer->product_id}", [
            'item_type' => 'produto',
            'name' => $offer->product->name,
            'price' => 100,
            'has_stock' => false,
            'stock_quantity' => 10,
        ])->assertStatus(422)->assertJsonValidationErrors('has_stock');

        $offer->refresh();

        $this->assertTrue((bool) $offer->has_stock);
        $this->assertSame(2, $offer->reserved_quantity);
    }

    public function test_a_reserva_continua_devolvivel_depois_da_tentativa(): void
    {
        $offer = $this->oferta(['stock_quantity' => 10]);
        $order = $this->pedidoDe($offer, 3);

        Sanctum::actingAs($offer->expositor->user);
        $this->putJson("/api/v1/lojista/produtos/{$offer->product_id}", [
            'item_type' => 'produto',
            'name' => $offer->product->name,
            'price' => 100,
            'has_stock' => false,
            'stock_quantity' => null,
        ])->assertStatus(422);

        app(ReleaseOrderStock::class)($order);

        $this->assertSame(0, $offer->fresh()->reserved_quantity);
        $this->assertSame(10, $offer->fresh()->disponivel());
    }

    public function test_sem_reserva_o_lojista_volta_a_desligar_o_controle(): void
    {
        // A semântica de ilimitado não foi tocada: o que é proibido é desligar
        // o controle **por cima** de unidades já prometidas.
        $offer = $this->oferta(['stock_quantity' => 10]);
        $order = $this->pedidoDe($offer, 3);

        app(ReleaseOrderStock::class)($order);
        $this->assertSame(0, $offer->fresh()->reserved_quantity);

        Sanctum::actingAs($offer->expositor->user);

        $this->putJson("/api/v1/lojista/produtos/{$offer->product_id}", [
            'item_type' => 'produto',
            'name' => $offer->product->name,
            'price' => 100,
            'has_stock' => false,
            'stock_quantity' => null,
        ])->assertOk();

        $offer->refresh();

        $this->assertFalse((bool) $offer->has_stock);
        $this->assertNull($offer->stock_quantity);
        $this->assertNull($offer->disponivel());
    }

    // ─── R-1: oferta com reserva ativa não pode ser apagada ─────────────────

    public function test_oferta_sem_reserva_continua_podendo_ser_excluida(): void
    {
        $offer = $this->oferta();

        Sanctum::actingAs($offer->expositor->user);
        $this->deleteJson("/api/v1/lojista/produtos/{$offer->product_id}")->assertNoContent();

        $this->assertDatabaseMissing('product_offers', ['id' => $offer->id]);
        // O produto mestre sobrevive à saída da oferta — regra da CAT-DOM-01.
        $this->assertDatabaseHas('products', ['id' => $offer->product_id]);
    }

    public function test_oferta_com_reserva_nao_e_excluida_e_o_pedido_fica_intacto(): void
    {
        $offer = $this->oferta(['stock_quantity' => 10]);
        $order = $this->pedidoDe($offer, 3);
        $item = $order->items->first();

        Sanctum::actingAs($offer->expositor->user);
        $this->deleteJson("/api/v1/lojista/produtos/{$offer->product_id}")->assertStatus(409);

        // A oferta continua sendo o recurso operacional daquela reserva.
        $this->assertDatabaseHas('product_offers', ['id' => $offer->id, 'reserved_quantity' => 3]);
        $this->assertSame($offer->id, $item->fresh()->product_offer_id);
        $this->assertSame(OrderStatus::AguardandoPagamento, $order->fresh()->status);
    }

    public function test_painel_do_lojista_explica_por_que_nao_excluiu(): void
    {
        $offer = $this->oferta(['stock_quantity' => 10]);
        $this->pedidoDe($offer, 2);

        Livewire::actingAs($offer->expositor->user)
            ->test(ProdutoIndex::class)
            ->call('delete', $offer->id)
            ->assertSee('não pode ser excluída agora');

        $this->assertDatabaseHas('product_offers', ['id' => $offer->id]);
    }

    public function test_desativar_com_reserva_e_permitido(): void
    {
        $offer = $this->oferta(['stock_quantity' => 10]);
        $this->pedidoDe($offer, 4);

        Livewire::actingAs($offer->expositor->user)
            ->test(ProdutoIndex::class)
            ->call('toggleActive', $offer->id);

        $offer->refresh();

        $this->assertFalse((bool) $offer->is_active);
        $this->assertSame(4, $offer->reserved_quantity);
        // Fora da vitrine, mas ainda existente para quem já comprou.
        $this->assertSame(0, ProductOffer::vigente()->whereKey($offer->id)->count());
    }

    public function test_reserva_de_uma_loja_nao_prende_a_oferta_de_outra(): void
    {
        $comReserva = $this->oferta(['stock_quantity' => 10]);
        $semReserva = $this->oferta(['stock_quantity' => 10]);

        $this->pedidoDe($comReserva, 2);

        Sanctum::actingAs($semReserva->expositor->user);
        $this->deleteJson("/api/v1/lojista/produtos/{$semReserva->product_id}")->assertNoContent();

        $this->assertDatabaseMissing('product_offers', ['id' => $semReserva->id]);
        $this->assertDatabaseHas('product_offers', ['id' => $comReserva->id, 'reserved_quantity' => 2]);
    }

    public function test_oferta_desativada_ainda_consome_e_libera_a_reserva(): void
    {
        // Sair da vitrine não pode tirar do pedido o direito à oferta que
        // comprometeu as unidades: nem `ConsumeOrderStock` nem
        // `ReleaseOrderStock` passam por `scopeVigente()`.
        $offer = $this->oferta(['stock_quantity' => 10]);
        $consumido = $this->pedidoDe($offer, 2);
        $liberado = $this->pedidoDe($offer, 3);

        $offer->update(['is_active' => false]);

        $this->pagar($consumido);
        app(ReleaseOrderStock::class)($liberado);

        $offer->refresh();

        $this->assertSame(8, $offer->stock_quantity);
        $this->assertSame(0, $offer->reserved_quantity);
        $this->assertNotNull($consumido->fresh()->stock_consumed_at);
        $this->assertNotNull($liberado->fresh()->stock_released_at);
    }

    // ─── O lojista não pode invalidar reservas ──────────────────────────────

    public function test_lojista_nao_reduz_o_estoque_abaixo_do_comprometido(): void
    {
        $offer = $this->oferta(['stock_quantity' => 10]);
        $this->pedidoDe($offer, 8);

        $lojista = $offer->expositor->user;
        Sanctum::actingAs($lojista);

        $this->putJson("/api/v1/lojista/produtos/{$offer->product_id}", [
            'item_type' => 'produto',
            'name' => $offer->product->name,
            'price' => 100,
            'has_stock' => true,
            'stock_quantity' => 5,
        ])->assertStatus(422)->assertJsonValidationErrors('stock_quantity');

        $this->assertSame(10, $offer->fresh()->stock_quantity);
    }

    public function test_formulario_do_lojista_mostra_por_que_recusou_a_reducao(): void
    {
        // A API devolve 422; o formulário precisa devolver a mesma recusa em
        // texto, embaixo do campo. Sem isso o lojista clica em salvar, nada
        // acontece e ele não descobre o motivo.
        $offer = $this->oferta(['stock_quantity' => 10]);
        $this->pedidoDe($offer, 8);

        $this->actingAs($offer->expositor->user);

        Livewire::test(ProdutoForm::class, ['product' => $offer->product])
            ->set('stock_quantity', 5)
            ->call('save')
            ->assertHasErrors('stock_quantity');

        $this->assertSame(10, $offer->fresh()->stock_quantity);
        $this->assertSame(8, $offer->fresh()->reserved_quantity);
    }

    public function test_lojista_pode_aumentar_o_estoque_sem_perder_reservas(): void
    {
        $offer = $this->oferta(['stock_quantity' => 10]);
        $this->pedidoDe($offer, 8);

        Sanctum::actingAs($offer->expositor->user);

        $this->putJson("/api/v1/lojista/produtos/{$offer->product_id}", [
            'item_type' => 'produto',
            'name' => $offer->product->name,
            'price' => 100,
            'has_stock' => true,
            'stock_quantity' => 20,
        ])->assertOk();

        $offer->refresh();
        $this->assertSame(20, $offer->stock_quantity);
        $this->assertSame(8, $offer->reserved_quantity);
        $this->assertSame(12, $offer->disponivel());
    }

    // ─── Estoque nunca fica negativo ────────────────────────────────────────

    public function test_estoque_e_reserva_nunca_ficam_negativos(): void
    {
        $offer = $this->oferta(['stock_quantity' => 2]);

        $this->pedidoDe($offer, 2);

        try {
            $this->pedidoDe($offer, 1);
        } catch (EstoqueInsuficiente) {
            // esperado
        }

        $offer->refresh();
        $this->assertGreaterThanOrEqual(0, $offer->stock_quantity);
        $this->assertGreaterThanOrEqual(0, $offer->reserved_quantity);
        $this->assertLessThanOrEqual($offer->stock_quantity, $offer->reserved_quantity);
    }
}
