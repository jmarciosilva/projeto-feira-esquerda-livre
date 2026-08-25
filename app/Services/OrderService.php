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

        $deliveryType = $customerData['delivery_type'] ?? 'entrega';
        $shippingNote = $customerData['shipping_note'] ?? null;
        $shippingNote ??= $deliveryType === 'retirada'
            ? 'Retirada combinada diretamente com o(s) lojista(s) via WhatsApp.'
            : ($settings->frete_mensagem_manual ?: 'Frete a combinar diretamente com o(s) lojista(s) via WhatsApp.');

        $order = DB::transaction(function () use ($customerData, $items, $itemsTotal, $shippingTotal, $cart, $shippingNote, $commission) {
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
                    'expositor_id' => $item->expositor_id,
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
                    'gross_amount' => $gross,
                    'commission_percent' => $commission,
                    'commission_amount' => $commissionAmount,
                    'net_amount' => $gross - $commissionAmount,
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
}
