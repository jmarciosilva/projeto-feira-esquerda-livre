<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\MercadoPagoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Throwable;

class MercadoPagoPaymentController extends Controller
{
    public function start(string $reference, MercadoPagoService $mercadoPago): RedirectResponse
    {
        $order = Order::where('reference', $reference)->with('items.product')->firstOrFail();

        if ($order->status === OrderStatus::PagamentoConfirmado) {
            return redirect()->route('pedido.show', $order->reference)
                ->with('success', 'Este pedido já está com pagamento confirmado.');
        }

        try {
            if (! $order->mercado_pago_preference_id) {
                $order = $mercadoPago->createPreference($order);
            }

            return redirect()->away($mercadoPago->checkoutUrl($order));
        } catch (Throwable $exception) {
            report($exception);

            return redirect()->route('pedido.show', $order->reference)
                ->with('error', 'Não foi possível abrir o Mercado Pago agora. Tente novamente em alguns instantes.');
        }
    }

    public function retorno(string $reference, Request $request, MercadoPagoService $mercadoPago): RedirectResponse
    {
        $order = Order::where('reference', $reference)->firstOrFail();
        $paymentId = $request->input('payment_id') ?: $request->input('collection_id');

        if ($paymentId && $paymentId !== 'null') {
            try {
                $order = $mercadoPago->syncPayment((string) $paymentId) ?? $order;
            } catch (Throwable $exception) {
                report($exception);
            }
        }

        $message = match ($order->payment_status) {
            'approved' => 'Pagamento confirmado pelo Mercado Pago.',
            'pending', 'in_process' => 'Pagamento recebido pelo Mercado Pago e aguardando confirmação.',
            'rejected' => 'O pagamento não foi aprovado. Você pode tentar novamente.',
            default => 'Retorno do Mercado Pago recebido.',
        };

        return redirect()->route('pedido.show', $order->reference)->with('success', $message);
    }

    public function webhook(Request $request, MercadoPagoService $mercadoPago): JsonResponse
    {
        $paymentId = data_get($request->all(), 'data.id')
            ?: $request->input('id')
            ?: $request->input('payment_id');

        $topic = $request->input('type') ?: $request->input('topic');

        if (! $paymentId || ($topic && ! in_array($topic, ['payment', 'payments'], true))) {
            return response()->json(['ok' => true]);
        }

        try {
            $mercadoPago->syncPayment((string) $paymentId);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json(['ok' => false], 500);
        }

        return response()->json(['ok' => true]);
    }
}
