<?php

namespace App\Livewire\Cliente\Ava;

use App\Models\Ava\AvaEnrollment;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class AprendizadoIndex extends Component
{
    public function render(): View
    {
        $enrollments = AvaEnrollment::where('user_id', auth()->id())
            // `orderSplit` entra no eager load porque a capa passa por ele:
            // a CAT-DOM-02G resolve a oferta de origem pela compra, e não pelo
            // estado atual do catálogo (D-02G-5).
            ->with(['course.product.expositor', 'orderSplit'])
            ->orderByDesc('enrolled_at')
            ->get();

        return view('livewire.cliente.ava.aprendizado-index', [
            'enrollments' => $enrollments,
        ])->layout('cliente.layouts.app', ['title' => 'Meu Aprendizado']);
    }
}
