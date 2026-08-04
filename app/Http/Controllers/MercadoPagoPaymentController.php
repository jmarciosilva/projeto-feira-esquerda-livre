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

    /** Recebe o formData do Payment Brick embutido e processa o pagamento direto via Checkout API. */
    public function pay(Request $request, string $reference, MercadoPagoService $mercadoPago): JsonResponse
    {
        $order = Order::where('reference', $reference)->firstOrFail();

        if ($order->status === OrderStatus::PagamentoConfirmado) {
            return response()->json(['status' => 'approved']);
        }

        $formData = $request->validate([
            'token' => 'nullable|string',
            'issuer_id' => 'nullable|string',
            'payment_method_id' => 'required|string|max:60',
            'installments' => 'nullable|integer|min:1|max:60',
            'payer' => 'required|array',
            'payer.email' => 'required|email|max:255',
            'payer.first_name' => 'nullable|string|max:255',
            'payer.last_name' => 'nullable|string|max:255',
            'payer.identification' => 'nullable|array',
            'payer.identification.type' => 'nullable|string|max:10',
            'payer.identification.number' => 'nullable|string|max:30',
            'payer.address' => 'nullable|array',
            'payer.phone' => 'nullable|array',
        ]);

        try {
            $payment = $mercadoPago->createPayment($order, $formData);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'status' => 'error',
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'status' => $payment['status'] ?? 'unknown',
            'status_detail' => $payment['status_detail'] ?? null,
            'payment_id' => $payment['id'] ?? null,
            'pix' => $this->extractPix($payment),
            'ticket_url' => $payment['transaction_details']['external_resource_url'] ?? null,
        ]);
    }

    /** Status resumido do pedido, usado pelo polling do Pix embutido para atualizar a página sozinha. */
    public function status(string $reference): JsonResponse
    {
        $order = Order::where('reference', $reference)->firstOrFail();

        return response()->json([
            'status' => $order->status === OrderStatus::PagamentoConfirmado ? 'approved' : ($order->payment_status ?? 'pending'),
        ]);
    }

    /**
     * @param  array<string, mixed>  $payment
     * @return array{qr_code: ?string, qr_code_base64: ?string}|null
     */
    private function extractPix(array $payment): ?array
    {
        $data = $payment['point_of_interaction']['transaction_data'] ?? null;

        if (! is_array($data)) {
            return null;
        }

        return [
            'qr_code' => $data['qr_code'] ?? null,
            'qr_code_base64' => $data['qr_code_base64'] ?? null,
        ];
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
