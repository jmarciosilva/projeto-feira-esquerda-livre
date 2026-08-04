<?php

namespace App\Livewire\Admin;

use App\Enums\OrderSplitStatus;
use App\Models\Banner;
use App\Models\CustomerProfile;
use App\Models\Event;
use App\Models\Expositor;
use App\Models\LojistasSolicitacao;
use App\Models\Media;
use App\Models\Order;
use App\Models\OrderSplit;
use App\Models\Page;
use App\Models\Post;
use App\Models\Product;
use Livewire\Component;

class Dashboard extends Component
{
    public function render(): \Illuminate\View\View
    {
        return view('livewire.admin.dashboard', [
            'stats' => [
                'pages'                 => Page::count(),
                'banners'               => Banner::count(),
                'posts'                 => Post::count(),
                'events'                => Event::count(),
                'media'                 => Media::count(),
                'products'              => Product::count(),
                'orders'                => Order::count(),
                'customers'             => CustomerProfile::count(),
                'solicitacoes_pendentes' => LojistasSolicitacao::pendentes()->count(),
            ],
            'recentPosts'  => Post::with('author')->latest()->take(5)->get(),
            'upcomingEvents' => Event::where('is_active', true)
                ->where('start_date', '>=', now())
                ->orderBy('start_date')
                ->take(5)
                ->get(),
            'recentSolicitacoes' => LojistasSolicitacao::pendentes()->latest()->take(3)->get(),
            'recentOrders' => Order::latest()->take(5)->get(),
        ] + $this->financeData())
            ->layout('admin.layouts.app', ['title' => 'Dashboard']);
    }

    /**
     * Faturamento do marketplace. A fonte de verdade é OrderSplit.status,
     * que é o único sinal confirmado tanto para Mercado Pago (confirmado
     * automaticamente na aprovação) quanto para pagamento manual (confirmado
     * pelo lojista) — Order.status não reflete pagamento manual.
     *
     * @return array<string, mixed>
     */
    private function financeData(): array
    {
        $confirmed = OrderSplit::where('status', OrderSplitStatus::Confirmado);

        $revenueTotal = (clone $confirmed)->sum('gross_amount');
        $commissionTotal = (clone $confirmed)->sum('commission_amount');
        $confirmedOrdersCount = (clone $confirmed)->distinct('order_id')->count('order_id');
        $averageOrderValue = $confirmedOrdersCount > 0 ? $revenueTotal / $confirmedOrdersCount : 0;

        $startOfThisMonth = now()->startOfMonth();
        $startOfLastMonth = now()->subMonthNoOverflow()->startOfMonth();
        $endOfLastMonth = now()->startOfMonth()->subSecond();

        $revenueThisMonth = (clone $confirmed)->where('confirmed_at', '>=', $startOfThisMonth)->sum('gross_amount');
        $revenueLastMonth = (clone $confirmed)->whereBetween('confirmed_at', [$startOfLastMonth, $endOfLastMonth])->sum('gross_amount');

        $revenueGrowthPercent = $revenueLastMonth > 0
            ? round((($revenueThisMonth - $revenueLastMonth) / $revenueLastMonth) * 100, 1)
            : null;

        $pending = OrderSplit::where('status', OrderSplitStatus::Pendente);
        $pendingAmount = (clone $pending)->sum('gross_amount');
        $pendingOrdersCount = (clone $pending)->distinct('order_id')->count('order_id');

        $dailyRevenue = $this->dailyRevenue();

        // Filtra e ordena em PHP em vez de HAVING/ORDER BY na coluna calculada:
        // HAVING sobre alias de subquery funciona no MySQL mas nao no SQLite (usado
        // nos testes), e a quantidade de lojas nunca e grande o suficiente para pesar.
        $topStores = Expositor::query()
            ->withSum(['orderSplits as confirmed_revenue' => fn ($q) => $q->where('status', OrderSplitStatus::Confirmado)], 'gross_amount')
            ->withCount(['orderSplits as confirmed_orders_count' => fn ($q) => $q->where('status', OrderSplitStatus::Confirmado)])
            ->get()
            ->filter(fn ($store) => (float) $store->confirmed_revenue > 0)
            ->sortByDesc('confirmed_revenue')
            ->take(5)
            ->values();

        return [
            'revenueTotal' => (float) $revenueTotal,
            'commissionTotal' => (float) $commissionTotal,
            'averageOrderValue' => (float) $averageOrderValue,
            'confirmedOrdersCount' => $confirmedOrdersCount,
            'revenueThisMonth' => (float) $revenueThisMonth,
            'revenueGrowthPercent' => $revenueGrowthPercent,
            'pendingAmount' => (float) $pendingAmount,
            'pendingOrdersCount' => $pendingOrdersCount,
            'dailyRevenue' => $dailyRevenue,
            'topStores' => $topStores,
        ];
    }

    /**
     * @return array<int, array{date: string, label: string, total: float}>
     */
    private function dailyRevenue(): array
    {
        $since = now()->subDays(29)->startOfDay();

        $byDay = OrderSplit::where('status', OrderSplitStatus::Confirmado)
            ->where('confirmed_at', '>=', $since)
            ->selectRaw('DATE(confirmed_at) as day, SUM(gross_amount) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        return collect(range(29, 0))
            ->map(function (int $daysAgo) use ($byDay) {
                $date = now()->subDays($daysAgo);

                return [
                    'date' => $date->toDateString(),
                    'label' => $date->isoFormat('DD/MM'),
                    'total' => (float) ($byDay[$date->toDateString()] ?? 0),
                ];
            })
            ->values()
            ->all();
    }
}
