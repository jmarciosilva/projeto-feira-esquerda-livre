<?php

namespace Tests\Feature;

use App\Actions\Stock\ReleaseOrderStock;
use App\Enums\UserRole;
use App\Livewire\Lojista\Pedidos\PedidoChat;
use App\Livewire\Lojista\Pedidos\PedidoIndex;
use App\Livewire\OrderChat;
use App\Models\Expositor;
use App\Models\Order;
use App\Models\OrderShipping;
use App\Models\Product;
use App\Models\ProductOffer;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\CartService;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * FIN-SEC-01B — o pedido é um fato histórico.
 *
 * Estes testes têm um alvo bem específico: eles falham se alguém reintroduzir
 * `CASCADE` entre o cadastro do vendedor e as tabelas de pedido, ou se o
 * snapshot do vendedor deixar de ser gravado na compra.
 *
 * O que a auditoria da FIN-SEC-01A reproduziu — excluir um expositor apagava
 * itens, splits, mensagens, envios e rastreio, deixando o `Order` de pé com um
 * total que nenhuma linha sustentava — não pode voltar sem quebrar aqui.
 */
class PreservacaoHistoricaTest extends TestCase
{
    use RefreshDatabase;

    private static int $counter = 0;

    /** @return array{expositor: Expositor, offer: ProductOffer} */
    private function loja(string $nome): array
    {
        self::$counter++;

        $user = User::factory()->create(['role' => UserRole::Lojista, 'is_active' => true]);

        $expositor = Expositor::create([
            'user_id' => $user->id,
            'name' => $nome,
            'slug' => 'loja-'.self::$counter,
            'is_active' => true,
        ]);

        $product = Product::factory()->create([
            'expositor_id' => $expositor->id,
            'item_type' => 'produto',
            'name' => 'Camiseta X '.self::$counter,
            'slug' => 'camiseta-x-'.self::$counter,
            'price' => 100,
        ]);

        return [
            'expositor' => $expositor,
            'offer' => $product->offers()->where('expositor_id', $expositor->id)->first(),
        ];
    }

    /** Cria um pedido real pelo caminho de produção, não por insert manual. */
    private function pedidoDe(ProductOffer $offer, int $qty = 2): Order
    {
        $cliente = User::factory()->create();
        $this->actingAs($cliente);

        app(CartService::class)->add($offer, $qty);

        return app(OrderService::class)->createFromCart([
            'customer_name' => 'Cliente',
            'customer_whatsapp' => '(11)99999-0000',
            'customer_email' => 'cliente@teste.com',
            'delivery_type' => 'retirada',
            'address_cep' => '01001000',
            'address_rua' => 'Rua Teste',
            'address_numero' => '10',
            'address_bairro' => 'Centro',
            'address_cidade' => 'Sao Paulo',
            'address_estado' => 'SP',
            'shipping_total' => 0,
        ], app(CartService::class));
    }

    public function test_excluir_expositor_preserva_o_pedido_e_seus_itens(): void
    {
        ['expositor' => $expositor, 'offer' => $offer] = $this->loja('Loja Original');
        $order = $this->pedidoDe($offer);

        $item = $order->items->first();
        $split = $order->splits->first();

        $expositor->delete();

        $this->assertDatabaseHas('orders', ['id' => $order->id]);
        $this->assertDatabaseHas('order_items', ['id' => $item->id]);
        $this->assertDatabaseHas('order_splits', ['id' => $split->id]);

        // O vínculo vivo se desfaz; o fato comercial permanece inteiro.
        $item->refresh();
        $this->assertNull($item->expositor_id);
        $this->assertSame('Loja Original', $item->expositor_name);
        $this->assertSame('200.00', $item->total_price);
        $this->assertNotEmpty($item->product_name);

        $split->refresh();
        $this->assertNull($split->expositor_id);
        $this->assertSame('Loja Original', $split->expositor_name);
        $this->assertSame('200.00', $split->gross_amount);
    }

    public function test_excluir_expositor_preserva_o_envio_e_o_rastreio(): void
    {
        ['expositor' => $expositor, 'offer' => $offer] = $this->loja('Loja Com Envio');
        $order = $this->pedidoDe($offer);
        $split = $order->splits->first();

        $shipping = OrderShipping::create([
            'order_id' => $order->id,
            'order_split_id' => $split->id,
            'expositor_id' => $expositor->id,
            'status' => 'shipped',
            'carrier' => 'Correios',
            'tracking_code' => 'AA123456789BR',
        ]);

        $expositor->delete();

        $this->assertDatabaseHas('order_shippings', ['id' => $shipping->id]);
        $this->assertNull($shipping->fresh()->expositor_id);
        $this->assertSame('AA123456789BR', $shipping->fresh()->tracking_code);
    }

    public function test_renomear_a_loja_nao_reescreve_pedidos_antigos(): void
    {
        ['expositor' => $expositor, 'offer' => $offer] = $this->loja('Loja Original');
        $order = $this->pedidoDe($offer);

        $expositor->update(['name' => 'Loja Renomeada']);

        $this->assertSame('Loja Original', $order->items->first()->fresh()->expositor_name);
        $this->assertSame('Loja Original', $order->splits->first()->fresh()->expositor_name);

        // E o cadastro vivo, esse sim, mudou.
        $this->assertSame('Loja Renomeada', $expositor->fresh()->name);
    }

    public function test_item_de_pedido_responde_sozinho_pelo_que_foi_vendido(): void
    {
        ['expositor' => $expositor, 'offer' => $offer] = $this->loja('Loja Autossuficiente');
        $order = $this->pedidoDe($offer);
        $item = $order->items->first();

        $nomeDoProduto = $item->product_name;

        // Desde a FIN-SEC-01E a oferta só pode sair enquanto ainda deve
        // unidades a um pedido em aberto se a reserva for devolvida antes — é
        // o caminho do cancelamento. O que este teste investiga vem depois: o
        // que sobra no pedido quando o mundo vivo desaparece.
        app(ReleaseOrderStock::class)($order);

        // Some tudo o que era vivo: oferta, produto e vendedor.
        $offer->delete();
        Product::whereKey($item->product_id)->delete();
        $expositor->delete();

        $item->refresh();

        $this->assertNull($item->product_id);
        $this->assertNull($item->product_offer_id);
        $this->assertNull($item->expositor_id);

        // ...e o item ainda responde às cinco perguntas do fato comercial.
        $this->assertSame($nomeDoProduto, $item->product_name);
        $this->assertSame('Loja Autossuficiente', $item->expositor_name);
        $this->assertSame('100.00', $item->unit_price);
        $this->assertSame(2, $item->quantity);
        $this->assertSame('200.00', $item->total_price);
    }

    public function test_split_historico_nao_muda_quando_a_comissao_atual_muda(): void
    {
        ['offer' => $offer] = $this->loja('Loja Comissao');
        $order = $this->pedidoDe($offer);
        $split = $order->splits->first();

        $percentualOriginal = $split->commission_percent;
        $valorOriginal = $split->commission_amount;

        SiteSetting::instance()->update(['comissao_percentual' => 50]);

        $split->refresh();
        $this->assertSame($percentualOriginal, $split->commission_percent);
        $this->assertSame($valorOriginal, $split->commission_amount);
    }

    public function test_pedido_de_loja_removida_continua_legivel_para_o_cliente(): void
    {
        ['expositor' => $expositor, 'offer' => $offer] = $this->loja('Loja Que Saiu');
        $order = $this->pedidoDe($offer);

        $expositor->delete();

        // A página do pedido não pode quebrar por causa de uma relação nula.
        $this->get(route('pedido.show', $order->reference))
            ->assertOk()
            ->assertSee('Loja Que Saiu');
    }

    // ─── Histórico órfão não vira permissão (revisão pré-commit) ────────────

    public function test_outro_expositor_nao_acessa_o_split_de_uma_loja_excluida(): void
    {
        ['expositor' => $lojaA, 'offer' => $offer] = $this->loja('Loja A');
        $order = $this->pedidoDe($offer);
        $split = $order->splits->first();

        ['expositor' => $lojaB] = $this->loja('Loja B');
        $lojaA->delete();

        $this->assertNull($split->fresh()->expositor_id);

        // O painel do lojista B não enxerga o split órfão...
        Livewire::actingAs($lojaB->user)
            ->test(PedidoIndex::class)
            ->assertDontSee($order->reference);

        // ...e nem o chat dele é alcançável, mesmo conhecendo o id.
        Livewire::actingAs($lojaB->user)
            ->test(PedidoChat::class, ['split' => $split->fresh()])
            ->assertForbidden();
    }

    public function test_visitante_anonimo_nao_entra_no_chat_de_split_orfao(): void
    {
        ['expositor' => $expositor, 'offer' => $offer] = $this->loja('Loja Some');
        $order = $this->pedidoDe($offer);
        $split = $order->splits->first();

        $expositor->delete();
        auth()->logout();

        // `null === null` não pode virar autorização: sem usuário autenticado
        // não há cliente nem lojista a reconhecer.
        Livewire::test(OrderChat::class, ['split' => $split->fresh()])
            ->assertForbidden();
    }

    public function test_cliente_continua_vendo_o_chat_de_um_pedido_de_loja_excluida(): void
    {
        ['expositor' => $expositor, 'offer' => $offer] = $this->loja('Loja Historica');
        $order = $this->pedidoDe($offer);
        $split = $order->splits->first();
        $cliente = $order->user;

        $expositor->delete();

        // O dono do pedido não perde o histórico da conversa.
        Livewire::actingAs($cliente)
            ->test(OrderChat::class, ['split' => $split->fresh()])
            ->assertOk();
    }

    public function test_api_do_pedido_responde_com_o_snapshot_quando_a_loja_sumiu(): void
    {
        ['expositor' => $expositor, 'offer' => $offer] = $this->loja('Loja Da API');
        $order = $this->pedidoDe($offer);
        $cliente = $order->user;

        $expositor->delete();

        Sanctum::actingAs($cliente);

        $this->getJson("/api/v1/pedidos/{$order->reference}")
            ->assertOk()
            ->assertJsonPath('data.splits.0.expositor.id', null)
            ->assertJsonPath('data.splits.0.expositor.name', 'Loja Da API');
    }

    public function test_excluir_o_proprio_pedido_leva_a_composicao_junto(): void
    {
        ['offer' => $offer] = $this->loja('Loja Composicao');
        $order = $this->pedidoDe($offer);
        $itemId = $order->items->first()->id;
        $splitId = $order->splits->first()->id;

        // CASCADE de `order_id` continua correto e proposital: item e split não
        // significam nada fora do pedido a que pertencem.
        $order->delete();

        $this->assertDatabaseMissing('order_items', ['id' => $itemId]);
        $this->assertDatabaseMissing('order_splits', ['id' => $splitId]);
    }
}
