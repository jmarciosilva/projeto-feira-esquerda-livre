<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Livewire\Admin\Dashboard;
use App\Models\Expositor;
use App\Models\Order;
use App\Models\OrderSplit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminDashboardFinanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_computes_confirmed_revenue_from_order_splits(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $storeA = Expositor::create(['name' => 'Loja A', 'slug' => 'loja-a']);
        $storeB = Expositor::create(['name' => 'Loja B', 'slug' => 'loja-b']);

        // Confirmado manualmente pelo lojista (sem Mercado Pago) - Order.status nao muda,
        // so o split. Precisa entrar no faturamento do mesmo jeito.
        $this->makeSplit($storeA, gross: 100, commission: 10, net: 90, status: 'confirmado', confirmedAt: now());

        // Confirmado via Mercado Pago.
        $this->makeSplit($storeB, gross: 50, commission: 5, net: 45, status: 'confirmado', confirmedAt: now()->subDays(2));

        // Ainda nao confirmado - nao pode contar como faturamento.
        $this->makeSplit($storeA, gross: 999, commission: 99.9, net: 899.1, status: 'pendente', confirmedAt: null);

        $component = Livewire::actingAs($admin)->test(Dashboard::class);

        $component->assertViewHas('revenueTotal', 150.0);
        $component->assertViewHas('commissionTotal', 15.0);
        $component->assertViewHas('confirmedOrdersCount', 2);
        $component->assertViewHas('pendingAmount', 999.0);
        $component->assertViewHas('pendingOrdersCount', 1);

        $topStores = $component->viewData('topStores');
        $this->assertSame('Loja A', $topStores->first()->name);
        $this->assertEquals(100.0, (float) $topStores->first()->confirmed_revenue);
    }

    public function test_pending_manual_orders_never_count_as_revenue_even_with_confirmed_at_set(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $store = Expositor::create(['name' => 'Loja C', 'slug' => 'loja-c']);

        // confirmed_at preenchido por engano nao deve importar - so o status manda.
        $this->makeSplit($store, gross: 500, commission: 50, net: 450, status: 'pendente', confirmedAt: now());

        $component = Livewire::actingAs($admin)->test(Dashboard::class);

        $component->assertViewHas('revenueTotal', 0.0);
        $component->assertViewHas('pendingAmount', 500.0);
    }

    private function makeSplit(
        Expositor $store,
        float $gross,
        float $commission,
        float $net,
        string $status,
        $confirmedAt,
    ): OrderSplit {
        $order = Order::create([
            'customer_name' => 'Cliente Teste',
            'customer_whatsapp' => '(11) 99999-9999',
            'delivery_type' => 'retirada',
            'items_total' => $gross,
            'shipping_total' => 0,
            'shipping_note' => 'Retirada combinada com a loja.',
            'total_amount' => $gross,
            'status' => 'aguardando_pagamento',
        ]);

        return OrderSplit::create([
            'order_id' => $order->id,
            'expositor_id' => $store->id,
            'gross_amount' => $gross,
            'commission_percent' => $gross > 0 ? round($commission / $gross * 100, 2) : 0,
            'commission_amount' => $commission,
            'net_amount' => $net,
            'status' => $status,
            'confirmed_at' => $confirmedAt,
        ]);
    }
}
