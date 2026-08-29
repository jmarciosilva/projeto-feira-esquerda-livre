<?php

namespace App\Actions\Stock;

use App\Actions\Stock\Concerns\OperaEstoqueDoPedido;
use App\Models\Order;
use Illuminate\Support\Facades\DB;

/**
 * Devolve ao disponível o que um pedido havia comprometido.
 *
 * Existe para o cancelamento e a expiração — que são assunto da FIN-SEC-01F.
 * Esta fase entrega a operação pronta e idempotente para que a próxima só
 * precise decidir **quando** chamá-la, sem reabrir a mecânica do estoque.
 *
 * Nunca devolve estoque de pedido já consumido: aquilo saiu da prateleira, e
 * repor é decisão de negócio — estorno, devolução —, não consequência de um
 * cancelamento tardio.
 */
final class ReleaseOrderStock
{
    use OperaEstoqueDoPedido;

    public function __invoke(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $atual = Order::query()
                ->whereKey($order->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            // Sem reserva, já liberada, ou já consumida: nada a devolver. As
            // três condições são o que torna esta ação segura de chamar duas
            // vezes.
            if ($atual->stock_reserved_at === null
                || $atual->stock_released_at !== null
                || $atual->stock_consumed_at !== null) {
                return;
            }

            $porOferta = $this->comprometidoPorOferta($atual);
            $ofertas = $this->travarOfertas($porOferta);

            foreach ($porOferta as $ofertaId => $quantidade) {
                $oferta = $ofertas[$ofertaId] ?? null;

                if ($oferta === null || ! $this->controlaEstoque($oferta)) {
                    continue;
                }

                // `min` protege o invariante mesmo diante de um estado
                // inesperado: comprometido nunca fica negativo.
                $oferta->decrement('reserved_quantity', min($quantidade, (int) $oferta->reserved_quantity));
            }

            $atual->forceFill(['stock_released_at' => now()])->save();
        });
    }
}
