<?php

namespace App\Services;

use App\CustomerIntelligence\Enums\EventName;
use App\CustomerIntelligence\Facades\CustomerIntelligence;
use App\Enums\MarketplaceStatus;
use App\Models\CustomerProfile;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderSplit;
use App\Models\SiteSetting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderService
{
    /**
     * Cria um pedido a partir do carrinho atual, com frete e pagamento manuais (MVP da Fase 4).
     *
     * @param  array<string, mixed>  $customerData
     */
    public function createFromCart(array $customerData, CartService $cart): Order
    {
        $items = $cart->items();

        if ($items->isEmpty()) {
            throw new \RuntimeException('O carrinho está vazio.');
        }

        $settings = SiteSetting::instance();
        $commission = (float) ($settings->comissao_percentual ?? 0);
        $itemsTotal = $cart->total();
        $shippingTotal = round((float) ($customerData['shipping_total'] ?? 0), 2);

        // Frete por loja, como o cliente o contratou. Chega do checkout web;
        // o checkout da API ainda envia apenas o total agregado.
        $shippingPorExpositor = $customerData['shipping_por_expositor'] ?? [];
        unset($customerData['shipping_por_expositor']);

        $deliveryType = $customerData['delivery_type'] ?? 'entrega';
        $shippingNote = $customerData['shipping_note'] ?? null;
        $shippingNote ??= $deliveryType === 'retirada'
            ? 'Retirada combinada diretamente com o(s) lojista(s) via WhatsApp.'
            : ($settings->frete_mensagem_manual ?: 'Frete a combinar diretamente com o(s) lojista(s) via WhatsApp.');

        $lojasDoPedido = $items->pluck('expositor_id')->unique();
        $lojasNoPedido = $lojasDoPedido->count();

        // O frete cobrado e a soma do que cada loja **deste pedido** cobrou.
        //
        // O carrinho pode ter mudado depois da cotacao — por outra aba, pelo
        // drawer, pela API — e a selecao de frete de uma loja que saiu ficaria
        // pendurada no total. Derivar o total das lojas presentes fecha a
        // divergencia entre `orders.shipping_total` e a soma dos splits, e
        // impede cobrar transporte de mercadoria que nao esta no pedido.
        if ($shippingPorExpositor !== []) {
            $shippingTotal = round(
                $lojasDoPedido
                    ->filter(fn ($id) => array_key_exists($id, $shippingPorExpositor))
                    ->sum(fn ($id) => (float) $shippingPorExpositor[$id]),
                2,
            );
        }

        $order = DB::transaction(function () use ($customerData, $items, $itemsTotal, $shippingTotal, $cart, $shippingNote, $commission, $shippingPorExpositor, $lojasNoPedido) {
            $order = Order::create([
                ...$customerData,
                'user_id' => Auth::id(),
                'session_id' => $items->first()->session_id,
                'items_total' => $itemsTotal,
                'shipping_total' => $shippingTotal,
                'shipping_note' => $shippingNote,
                'total_amount' => $itemsTotal + $shippingTotal,
                'status' => 'aguardando_pagamento',
            ]);

            foreach ($items as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'product_offer_id' => $item->product_offer_id,
                    'expositor_id' => $item->expositor_id,
                    // Snapshot do vendedor, gravado uma vez e nunca recalculado:
                    // renomear ou excluir a loja depois nao reescreve o pedido.
                    'expositor_name' => $item->expositor?->name,
                    'product_name' => $item->product?->name ?? 'Item removido',
                    'unit_price' => $item->price_snapshot,
                    'quantity' => $item->quantity,
                    'total_price' => $item->subtotal(),
                ]);
            }

            foreach ($items->groupBy('expositor_id') as $expositorId => $storeItems) {
                $gross = $storeItems->sum(fn ($item) => $item->subtotal());
                $commissionAmount = round($gross * ($commission / 100), 2);

                OrderSplit::create([
                    'order_id' => $order->id,
                    'expositor_id' => $expositorId,
                    'expositor_name' => $storeItems->first()->expositor?->name,
                    'gross_amount' => $gross,
                    'commission_percent' => $commission,
                    'commission_amount' => $commissionAmount,
                    'net_amount' => $gross - $commissionAmount,
                    'shipping_amount' => $this->freteDaLoja(
                        $expositorId,
                        $shippingPorExpositor,
                        $shippingTotal,
                        $lojasNoPedido,
                    ),
                    'status' => 'pendente',
                ]);
            }

            $cart->clear();

            // Garante que usuários autenticados (incluindo admins que compram) tenham perfil de cliente
            if ($userId = Auth::id()) {
                CustomerProfile::firstOrCreate(
                    ['user_id' => $userId],
                    ['marketplace_status' => MarketplaceStatus::Active->value]
                );
            }

            return $order->load(['items', 'splits.expositor']);
        });

        try {
            CustomerIntelligence::track(EventName::PedidoCriado, [
                'pedido_id' => $order->id,
                'referencia' => $order->reference,
                'valor_total' => (float) $order->total_amount,
                'quantidade_itens' => $order->items->sum('quantity'),
                'status_pagamento' => $order->status,
            ], $order);
        } catch (\Throwable $exception) {
            report($exception);
        }

        return $order;
    }

    /**
     * Quanto do frete pertence a esta loja — ou `null` quando nao da para saber.
     *
     * Tres situacoes, nesta ordem:
     *
     * 1. o checkout informou o valor por loja: e o fato, usa-se ele;
     * 2. o pedido nao teve frete, ou tem uma unica loja: a divisao e deduzivel
     *    com seguranca;
     * 3. veio so o total agregado de um pedido com varias lojas: a divisao e
     *    desconhecida, e `null` diz isso. Ratear por conta propria seria
     *    inventar um fato comercial que ninguem afirmou.
     *
     * @param  array<int|string, float|int|string>  $shippingPorExpositor
     */
    private function freteDaLoja(
        int|string|null $expositorId,
        array $shippingPorExpositor,
        float $shippingTotal,
        int $lojasNoPedido,
    ): ?float {
        if (array_key_exists($expositorId, $shippingPorExpositor)) {
            return round((float) $shippingPorExpositor[$expositorId], 2);
        }

        if ($shippingTotal <= 0.0 || $lojasNoPedido === 1) {
            return $shippingTotal;
        }

        return null;
    }
}
