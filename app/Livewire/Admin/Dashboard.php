<?php

namespace App\Livewire\Admin;

use App\Models\Banner;
use App\Models\CustomerProfile;
use App\Models\Event;
use App\Models\LojistasSolicitacao;
use App\Models\Media;
use App\Models\Order;
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
        ])->layout('admin.layouts.app', ['title' => 'Dashboard']);
    }
}
