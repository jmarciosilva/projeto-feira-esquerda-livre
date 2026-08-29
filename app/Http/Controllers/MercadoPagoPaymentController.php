<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\MercadoPagoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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

    /** Tópicos que este webhook sabe rotear. Qualquer outro é registrado e ignorado. */
    private const TOPICOS_DE_PAGAMENTO = ['payment', 'payments'];

    private const TOPICO_DE_CHARGEBACK = 'topic_chargebacks_wh';

    /**
     * Recebe as notificações do Mercado Pago.
     *
     * A allowlist é explícita de propósito: aceitar qualquer tópico faria o
     * webhook agir sobre eventos cujo formato ninguém verificou. O que fica de
     * fora dela deixa de sumir em silêncio — até a FIN-SEC-01F-A, um chargeback
     * sob tópico próprio recebia 200 OK e não deixava nem rastro de ter
     * chegado, o que tornava impossível descobrir que ele existia.
     *
     * O log carrega **metadados, nunca o payload**: identificar o evento é
     * suficiente para investigar, e o corpo cru pode trazer dado de pagador.
     */
    public function webhook(Request $request, MercadoPagoService $mercadoPago): JsonResponse
    {
        $topic = $request->input('type') ?: $request->input('topic');
        $dataId = data_get($request->all(), 'data.id')
            ?: $request->input('id')
            ?: $request->input('payment_id');

        try {
            if ($topic === self::TOPICO_DE_CHARGEBACK) {
                // `data.id` é o chargeback; quem liga ao pedido é
                // `data.payment_id`. Confundir os dois faria o domínio
                // procurar um pagamento que não existe.
                $mercadoPago->applyChargeback([
                    'id' => $dataId,
                    'payment_id' => data_get($request->all(), 'data.payment_id'),
                ]);

                return response()->json(['ok' => true]);
            }

            if ($dataId && (! $topic || in_array($topic, self::TOPICOS_DE_PAGAMENTO, true))) {
                $mercadoPago->syncPayment((string) $dataId);

                return response()->json(['ok' => true]);
            }
        } catch (Throwable $exception) {
            report($exception);

            return response()->json(['ok' => false], 500);
        }

        Log::info('mercado_pago.webhook.ignorado', [
            'topic' => $topic,
            'data_id' => $dataId,
            'recebido_em' => now()->toIso8601String(),
        ]);

        return response()->json(['ok' => true]);
    }
}
