<?php

/**
 * FIN-SEC-01G — arnês de concorrência em MySQL real.
 *
 * ## Por que isto não é um teste PHPUnit
 *
 * A suíte roda em SQLite, que não tem lock de linha nem MVCC: `lockForUpdate()`
 * vira no-op e toda prova de concorrência passa por acidente. E mesmo em MySQL,
 * um teste de processo único não consegue observar um lado **bloqueado**
 * enquanto o outro trabalha — quem bloqueia é o processo inteiro.
 *
 * Disputa de verdade precisa de dois processos e um banco de verdade. É o que
 * `prove.sh` faz: sobe um banco descartável, dispara pares de processos que
 * seguram o lock por um tempo conhecido, e confere o resultado.
 *
 * Uso (sempre via `prove.sh`):
 *
 *     php harness.php seed   <cenario> <ref>
 *     php harness.php worker <acao> <ref> <atrasoMs> <seguraMs>
 *     php harness.php estado <ref>
 *     php harness.php invariantes
 */

use App\Actions\Catalog\DeleteProductOffer;
use App\Actions\Orders\CancelOrder;
use App\Actions\Orders\ExpireOrder;
use App\Actions\Payments\ConfirmOrderPayment;
use App\Actions\Payments\RegisterPaymentConflict;
use App\Actions\Payments\ReverseOrderPayment;
use App\Actions\Stock\ReserveOrderStock;
use App\DTO\PaymentConfirmation;
use App\Enums\OrderSplitStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentConflictType;
use App\Enums\UserRole;
use App\Models\Expositor;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderSplit;
use App\Models\PaymentConflict;
use App\Models\Product;
use App\Models\ProductOffer;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

$raiz = dirname(__DIR__, 2);
require $raiz.'/vendor/autoload.php';
$app = require $raiz.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

/**
 * Catálogo mínimo, criado uma vez e reaproveitado por todos os cenários.
 *
 * @return array{0: Expositor, 1: Product, 2: ProductOffer}
 */
function catalogo(): array
{
    $lojista = User::firstOrCreate(
        ['email' => 'lojista.finsec01g@teste.local'],
        ['name' => 'Lojista 01G', 'password' => bcrypt('x'), 'role' => UserRole::Lojista, 'is_active' => true],
    );

    $expositor = Expositor::firstOrCreate(
        ['slug' => 'finsec01g-loja'],
        ['user_id' => $lojista->id, 'name' => 'Loja 01G', 'is_active' => true],
    );

    $product = Product::firstOrCreate(
        ['slug' => 'finsec01g-item'],
        ['expositor_id' => $expositor->id, 'name' => 'Item 01G', 'price' => 100, 'item_type' => 'produto', 'is_active' => true],
    );

    $offer = ProductOffer::firstOrCreate(
        ['product_id' => $product->id],
        ['expositor_id' => $expositor->id, 'price' => 100],
    );

    return [$expositor, $product, $offer];
}

/** Zera o movimento comercial sem derrubar o catálogo. */
function limpar(): void
{
    DB::statement('SET FOREIGN_KEY_CHECKS=0');

    foreach (['payment_conflicts', 'order_shippings', 'order_splits', 'order_items', 'orders'] as $tabela) {
        DB::table($tabela)->delete();
    }

    DB::statement('SET FOREIGN_KEY_CHECKS=1');
}

/**
 * Monta um pedido no estágio pedido.
 *
 * `pendente`  — reservado, ainda pagável
 * `pago`      — confirmado, estoque já consumido
 * `semreserva` — legado pré-01E: pagável e sem reserva nenhuma
 */
function semear(string $cenario, string $ref, int $estoque = 10, int $quantidade = 2): void
{
    [$expositor, $product, $offer] = catalogo();

    $pago = $cenario === 'pago';

    $offer->update([
        'has_stock' => true,
        'stock_quantity' => $estoque,
        'reserved_quantity' => 0,
        'is_active' => true,
    ]);

    $order = Order::create([
        'reference' => $ref,
        'customer_name' => 'Cliente 01G',
        'customer_whatsapp' => '(11)99999-0000',
        'delivery_type' => 'retirada',
        'items_total' => 100 * $quantidade,
        'shipping_total' => 0,
        'total_amount' => 100 * $quantidade,
        'payment_method' => 'mercado_pago',
        'payment_provider' => 'mercado_pago',
        'payment_status' => $pago ? 'approved' : 'pending',
        'status' => $pago ? OrderStatus::PagamentoConfirmado : OrderStatus::AguardandoPagamento,
        'paid_at' => $pago ? now() : null,
    ]);

    $order->forceFill([
        'mercado_pago_payment_id' => '9001',
        // Prazo já vencido: os cenários de expiração precisam que o relógio já
        // tenha passado, e `ExpireOrder` recusa expirar sem prazo vencido.
        'payment_expires_at' => now()->subMinute(),
    ])->save();

    OrderItem::create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'product_offer_id' => $offer->id,
        'expositor_id' => $expositor->id,
        'expositor_name' => $expositor->name,
        'product_name' => $product->name,
        'unit_price' => 100,
        'quantity' => $quantidade,
        'total_price' => 100 * $quantidade,
    ]);

    OrderSplit::create([
        'order_id' => $order->id,
        'expositor_id' => $expositor->id,
        'expositor_name' => $expositor->name,
        'gross_amount' => 100 * $quantidade,
        'commission_percent' => 10,
        'commission_amount' => 10 * $quantidade,
        'net_amount' => 90 * $quantidade,
        'shipping_amount' => 0,
        'status' => $pago ? OrderSplitStatus::Confirmado : OrderSplitStatus::Pendente,
        'confirmed_at' => $pago ? now() : null,
    ]);

    if ($cenario === 'pendente') {
        app(ReserveOrderStock::class)($order->fresh());
    }

    if ($pago) {
        // Estado equivalente ao pós-confirmação: físico baixado, nada reservado.
        $offer->decrement('stock_quantity', $quantidade);
        $order->forceFill(['stock_reserved_at' => now(), 'stock_consumed_at' => now()])->save();
    }
}

/** Um lado da disputa. O `usleep` roda **dentro** da transação, após o lock. */
function trabalhar(string $acao, string $ref, int $antesMs, int $seguraMs): void
{
    usleep($antesMs * 1000);

    $rotulo = str_pad(strtoupper($acao).'(t+'.$antesMs.'ms)', 26);
    $inicio = microtime(true);
    $segurou = false;

    DB::listen(function ($q) use (&$segurou, $seguraMs) {
        if ($seguraMs > 0 && ! $segurou && str_contains(strtolower($q->sql), 'for update')) {
            $segurou = true;
            usleep($seguraMs * 1000);
        }
    });

    try {
        $resultado = executar($acao, $ref);
        printf("  %s OK        %5dms  %s\n", $rotulo, round((microtime(true) - $inicio) * 1000), $resultado);
    } catch (Throwable $e) {
        printf("  %s RECUSADO  %5dms  %s\n", $rotulo, round((microtime(true) - $inicio) * 1000), class_basename($e));
    }
}

function executar(string $acao, string $ref): string
{
    $pedido = fn () => Order::where('reference', $ref)->firstOrFail();

    return match ($acao) {
        'confirmar' => app(ConfirmOrderPayment::class)($pedido(), new PaymentConfirmation(
            provider: 'mercado_pago',
            externalPaymentId: '9001',
            amount: (float) $pedido()->total_amount,
            paidAt: now(),
        ))->status->value,

        'reverter' => app(ReverseOrderPayment::class)($pedido(), 'refunded')->status->value,
        'cancelar' => app(CancelOrder::class)($pedido())->status->value,
        'expirar' => app(ExpireOrder::class)($pedido())->status->value,

        // Checkout: reservar é o que disputa a última peça.
        'reservar' => (function () use ($pedido) {
            app(ReserveOrderStock::class)($pedido());

            return 'reservado';
        })(),

        // O lojista mexendo no estoque enquanto alguém compra.
        'baixar-estoque' => (function () {
            [, , $offer] = catalogo();
            DB::transaction(function () use ($offer) {
                $atual = ProductOffer::whereKey($offer->id)->lockForUpdate()->first();
                $atual->update(['stock_quantity' => 0]);
            });

            return 'estoque zerado';
        })(),

        'excluir-oferta' => (function () {
            [, , $offer] = catalogo();
            app(DeleteProductOffer::class)($offer->fresh());

            return 'oferta excluída';
        })(),

        'conflito' => (function () use ($pedido) {
            $c = app(RegisterPaymentConflict::class)(
                $pedido(),
                PaymentConflictType::InsufficientStock,
                'mercado_pago',
                '9001',
                200.0,
                ['origem' => 'arnes-01g'],
            );

            return 'conflito#'.$c->id;
        })(),

        default => throw new InvalidArgumentException("Ação desconhecida: {$acao}"),
    };
}

function estado(string $ref): void
{
    $order = Order::where('reference', $ref)->with('splits')->first();

    if (! $order) {
        echo "  (pedido {$ref} inexistente)\n";

        return;
    }

    [, , $offer] = catalogo();
    $offer = ProductOffer::find($offer->id);
    $split = $order->splits->first();

    printf(
        "  → pedido=%s  paid_at=%s  reversed_at=%s  split=%s  estoque=%s  reservado=%d  conflitos=%d\n",
        $order->status->value,
        $order->paid_at ? 'sim' : 'não',
        $order->reversed_at ? 'sim' : 'não',
        $split?->status->value ?? '—',
        $offer?->stock_quantity ?? '—',
        (int) ($offer?->reserved_quantity ?? 0),
        PaymentConflict::count(),
    );
}

/**
 * Os estados que o domínio promete nunca produzir.
 *
 * Rodam sobre tudo que os cenários deixaram no banco. Zero em todas as linhas é
 * a única saída aceitável.
 */
function invariantes(): int
{
    $consultas = [
        'estornado sem paid_at' => "SELECT COUNT(*) c FROM orders WHERE status='estornado' AND paid_at IS NULL",
        'estornado sem reversed_at' => "SELECT COUNT(*) c FROM orders WHERE status='estornado' AND reversed_at IS NULL",
        'estornado com estoque devolvido' => "SELECT COUNT(*) c FROM orders WHERE status='estornado' AND stock_released_at IS NOT NULL",
        'pago com estoque devolvido' => "SELECT COUNT(*) c FROM orders WHERE status='pagamento_confirmado' AND stock_released_at IS NOT NULL",
        'expirado com estoque consumido' => "SELECT COUNT(*) c FROM orders WHERE status='expirado' AND stock_consumed_at IS NOT NULL",
        'cancelado com estoque consumido' => "SELECT COUNT(*) c FROM orders WHERE status='cancelado' AND stock_consumed_at IS NOT NULL",
        'consumido e liberado ao mesmo tempo' => 'SELECT COUNT(*) c FROM orders WHERE stock_consumed_at IS NOT NULL AND stock_released_at IS NOT NULL',
        'split revertido sem reverted_at' => "SELECT COUNT(*) c FROM order_splits WHERE status='revertido' AND reverted_at IS NULL",
        'split confirmado em pedido encerrado' => "SELECT COUNT(*) c FROM order_splits s JOIN orders o ON o.id=s.order_id WHERE s.status='confirmado' AND o.status IN ('cancelado','expirado')",
        'reserva acima do físico' => 'SELECT COUNT(*) c FROM product_offers WHERE stock_quantity IS NOT NULL AND reserved_quantity > stock_quantity',
        'reserva órfã' => 'SELECT COUNT(*) c FROM product_offers o WHERE o.reserved_quantity > 0 AND NOT EXISTS (SELECT 1 FROM order_items i JOIN orders p ON p.id=i.order_id WHERE i.product_offer_id=o.id AND p.stock_reserved_at IS NOT NULL AND p.stock_released_at IS NULL AND p.stock_consumed_at IS NULL)',
    ];

    $violacoes = 0;

    foreach ($consultas as $nome => $sql) {
        $c = (int) DB::selectOne($sql)->c;
        $violacoes += $c;
        printf("  %-40s %s\n", $nome, $c === 0 ? 'ok' : "VIOLADO ({$c})");
    }

    // `reserved_quantity` e `stock_quantity` são INT UNSIGNED NOT NULL/NULL no
    // schema: valor negativo não é improvável, é impossível — o MySQL em
    // STRICT_TRANS_TABLES recusa o UPDATE em vez de truncar.
    return $violacoes;
}

$comando = $argv[1] ?? 'estado';

exit(match ($comando) {
    'seed' => (function () use ($argv) {
        semear($argv[2], $argv[3], (int) ($argv[4] ?? 10), (int) ($argv[5] ?? 2));

        return 0;
    })(),
    'limpar' => (function () {
        limpar();

        return 0;
    })(),
    'worker' => (function () use ($argv) {
        trabalhar($argv[2], $argv[3], (int) $argv[4], (int) $argv[5]);

        return 0;
    })(),
    'estado' => (function () use ($argv) {
        estado($argv[2]);

        return 0;
    })(),
    'invariantes' => invariantes() === 0 ? 0 : 1,
    default => 1,
});
