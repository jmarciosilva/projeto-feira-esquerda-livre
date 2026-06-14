<?php

namespace App\Livewire\Lojista;

use Livewire\Component;

class Dashboard extends Component
{
    public function render(): \Illuminate\View\View
    {
        $expositor = auth()->user()->expositor;

        $upcomingEvents = $expositor
            ? $expositor->events()
                ->where('start_date', '>=', now())
                ->orderBy('start_date')
                ->take(5)
                ->get()
            : collect();

        return view('livewire.lojista.dashboard', [
            'expositor'      => $expositor,
            'totalProdutos'  => $expositor?->products()->count() ?? 0,
            'upcomingEvents' => $upcomingEvents,
        ])->layout('lojista.layouts.app', ['title' => 'Painel do Lojista']);
    }
}
