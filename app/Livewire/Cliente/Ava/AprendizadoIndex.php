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
            ->with(['course.product.expositor'])
            ->orderByDesc('enrolled_at')
            ->get();

        return view('livewire.cliente.ava.aprendizado-index', [
            'enrollments' => $enrollments,
        ])->layout('cliente.layouts.app', ['title' => 'Meu Aprendizado']);
    }
}
