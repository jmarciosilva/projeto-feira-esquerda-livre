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
            // "Produtos cadastrados" e o mesmo numero que "Meus Cadastros"
            // lista, e aquela tela lista ofertas. Contar pela relacao legada
            // `products` — que le `products.expositor_id`, hoje apenas
            // proveniencia — divergia dela assim que o lojista removia um item:
            // `DeleteProductOffer` apaga a oferta e deixa o produto no
            // catalogo, entao o painel continuava contando o que a listagem ja
            // nao mostrava.
            'totalProdutos'  => $expositor?->offers()->count() ?? 0,
            'upcomingEvents' => $upcomingEvents,
        ])->layout('lojista.layouts.app', ['title' => 'Painel do Lojista']);
    }
}
