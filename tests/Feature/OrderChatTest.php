<?php

namespace Tests\Feature;

use App\Enums\OrderSplitStatus;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Livewire\OrderChat;
use App\Models\Expositor;
use App\Models\Order;
use App\Models\OrderMessage;
use App\Models\OrderSplit;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class OrderChatTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    // ── Factories ─────────────────────────────────────────────────────────────

    private function makeLojista(): User
    {
        $u = User::factory()->create(['role' => UserRole::Lojista, 'is_active' => true]);
        $u->assignRole('lojista');
        return $u;
    }

    private function makeCliente(): User
    {
        $u = User::factory()->create(['role' => UserRole::User, 'is_active' => true]);
        $u->assignRole('cliente');
        return $u;
    }

    private function makeExpositor(User $lojista): Expositor
    {
        return Expositor::create([
            'user_id'     => $lojista->id,
            'name'        => 'Loja Chat ' . uniqid(),
            'slug'        => 'loja-chat-' . uniqid(),
            'description' => 'desc',
            'is_active'   => true,
            'is_featured' => false,
        ]);
    }

    private function makeSplit(User $cliente, Expositor $expositor): OrderSplit
    {
        $order = Order::create([
            'user_id'            => $cliente->id,
            'customer_name'      => $cliente->name,
            'customer_email'     => $cliente->email,
            'customer_whatsapp'  => '11999990000',
            'delivery_type'      => 'retirada',
            'items_total'        => 100,
            'shipping_total'     => 0,
            'total_amount'       => 100,
            'payment_method'     => 'manual',
            'status'             => OrderStatus::AguardandoPagamento,
        ]);

        return OrderSplit::create([
            'order_id'           => $order->id,
            'expositor_id'       => $expositor->id,
            'gross_amount'       => 100,
            'commission_percent' => 10,
            'commission_amount'  => 10,
            'net_amount'         => 90,
            'status'             => OrderSplitStatus::Pendente,
        ]);
    }

    // ── Testes ─────────────────────────────────────────────────────────────────

    public function test_cliente_can_send_message(): void
    {
        $lojista   = $this->makeLojista();
        $expositor = $this->makeExpositor($lojista);
        $cliente   = $this->makeCliente();
        $split     = $this->makeSplit($cliente, $expositor);

        Livewire::actingAs($cliente)
            ->test(OrderChat::class, ['split' => $split])
            ->set('body', 'Olá, quando chega meu pedido?')
            ->call('send')
            ->assertSet('body', '');

        $this->assertDatabaseHas('order_messages', [
            'order_split_id' => $split->id,
            'sender_id'      => $cliente->id,
            'body'           => 'Olá, quando chega meu pedido?',
        ]);
    }

    public function test_lojista_can_send_message(): void
    {
        $lojista   = $this->makeLojista();
        $expositor = $this->makeExpositor($lojista);
        $cliente   = $this->makeCliente();
        $split     = $this->makeSplit($cliente, $expositor);

        Livewire::actingAs($lojista)
            ->test(OrderChat::class, ['split' => $split])
            ->set('body', 'Seu pedido chega em 3 dias úteis!')
            ->call('send')
            ->assertSet('body', '');

        $this->assertDatabaseHas('order_messages', [
            'order_split_id' => $split->id,
            'sender_id'      => $lojista->id,
            'body'           => 'Seu pedido chega em 3 dias úteis!',
        ]);
    }

    public function test_body_is_required(): void
    {
        $lojista   = $this->makeLojista();
        $expositor = $this->makeExpositor($lojista);
        $cliente   = $this->makeCliente();
        $split     = $this->makeSplit($cliente, $expositor);

        Livewire::actingAs($cliente)
            ->test(OrderChat::class, ['split' => $split])
            ->set('body', '')
            ->call('send')
            ->assertHasErrors(['body']);
    }

    public function test_body_max_2000_chars(): void
    {
        $lojista   = $this->makeLojista();
        $expositor = $this->makeExpositor($lojista);
        $cliente   = $this->makeCliente();
        $split     = $this->makeSplit($cliente, $expositor);

        Livewire::actingAs($cliente)
            ->test(OrderChat::class, ['split' => $split])
            ->set('body', str_repeat('a', 2001))
            ->call('send')
            ->assertHasErrors(['body']);
    }

    public function test_third_party_lojista_cannot_access_split_chat_page(): void
    {
        $lojista1  = $this->makeLojista();
        $lojista2  = $this->makeLojista();
        $expositor = $this->makeExpositor($lojista1);
        $cliente   = $this->makeCliente();
        $split     = $this->makeSplit($cliente, $expositor);

        // Via rota HTTP: mount() de PedidoChat chama abort(403) para lojista que não é dono
        $this->actingAs($lojista2)
             ->get(route('lojista.pedidos.chat', $split->id))
             ->assertForbidden();
    }

    public function test_messages_marked_read_on_render(): void
    {
        $lojista   = $this->makeLojista();
        $expositor = $this->makeExpositor($lojista);
        $cliente   = $this->makeCliente();
        $split     = $this->makeSplit($cliente, $expositor);

        // Lojista envia mensagem sem ler
        OrderMessage::create([
            'order_split_id' => $split->id,
            'sender_id'      => $lojista->id,
            'body'           => 'Mensagem do lojista',
            'read_at'        => null,
        ]);

        // Cliente abre o chat — render() chama markRead()
        Livewire::actingAs($cliente)
            ->test(OrderChat::class, ['split' => $split]);

        $msg = OrderMessage::where('order_split_id', $split->id)->first();
        $this->assertNotNull($msg->read_at);
    }

    public function test_own_messages_not_marked_read_by_sender(): void
    {
        $lojista   = $this->makeLojista();
        $expositor = $this->makeExpositor($lojista);
        $cliente   = $this->makeCliente();
        $split     = $this->makeSplit($cliente, $expositor);

        // Cliente envia mensagem
        OrderMessage::create([
            'order_split_id' => $split->id,
            'sender_id'      => $cliente->id,
            'body'           => 'Minha própria mensagem',
            'read_at'        => null,
        ]);

        // Cliente abre o chat — suas próprias mensagens NÃO são marcadas
        Livewire::actingAs($cliente)
            ->test(OrderChat::class, ['split' => $split]);

        $msg = OrderMessage::where('order_split_id', $split->id)->first();
        $this->assertNull($msg->read_at);
    }

    public function test_messages_appear_in_chat(): void
    {
        $lojista   = $this->makeLojista();
        $expositor = $this->makeExpositor($lojista);
        $cliente   = $this->makeCliente();
        $split     = $this->makeSplit($cliente, $expositor);

        OrderMessage::create([
            'order_split_id' => $split->id,
            'sender_id'      => $cliente->id,
            'body'           => 'Pergunta do cliente sobre entrega',
        ]);

        Livewire::actingAs($lojista)
            ->test(OrderChat::class, ['split' => $split])
            ->assertSee('Pergunta do cliente sobre entrega');
    }

    public function test_unread_count_reflects_in_db(): void
    {
        $lojista   = $this->makeLojista();
        $expositor = $this->makeExpositor($lojista);
        $cliente   = $this->makeCliente();
        $split     = $this->makeSplit($cliente, $expositor);

        OrderMessage::create(['order_split_id' => $split->id, 'sender_id' => $cliente->id, 'body' => 'M1']);
        OrderMessage::create(['order_split_id' => $split->id, 'sender_id' => $cliente->id, 'body' => 'M2']);

        $unread = OrderMessage::where('order_split_id', $split->id)
            ->where('sender_id', '!=', $lojista->id)
            ->whereNull('read_at')
            ->count();

        $this->assertEquals(2, $unread);
    }

    public function test_lojista_chat_page_is_accessible(): void
    {
        $lojista   = $this->makeLojista();
        $expositor = $this->makeExpositor($lojista);
        $cliente   = $this->makeCliente();
        $split     = $this->makeSplit($cliente, $expositor);

        $this->actingAs($lojista)
             ->get(route('lojista.pedidos.chat', $split->id))
             ->assertOk();
    }

    public function test_lojista_cannot_access_other_lojista_chat(): void
    {
        $lojista1  = $this->makeLojista();
        $lojista2  = $this->makeLojista();
        $expositor = $this->makeExpositor($lojista1);
        $cliente   = $this->makeCliente();
        $split     = $this->makeSplit($cliente, $expositor);

        $this->actingAs($lojista2)
             ->get(route('lojista.pedidos.chat', $split->id))
             ->assertForbidden();
    }
}
