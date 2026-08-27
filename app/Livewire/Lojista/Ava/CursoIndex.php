<?php

namespace App\Livewire\Lojista\Ava;

use App\Models\Product;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class CursoIndex extends Component
{
    public function render(): View
    {
        $expositor = auth()->user()->expositor;

        $cursos = Product::whereHas('offers', fn ($o) => $o->where('expositor_id', $expositor->id))
            ->where('is_digital', true)
            ->with(['avaCourse', 'offers' => fn ($o) => $o->where('expositor_id', $expositor->id)])
            ->orderBy('name')
            ->get()
            ->map(function (Product $product) {
                $course = $product->avaCourse;

                return [
                    'product' => $product,
                    // O preco exibido e o da oferta deste lojista, nunca a
                    // coluna legada de `products` (CAT-DOM-01, divida D-1).
                    'offer' => $product->offers->first(),
                    'course' => $course,
                    'total_enrollments' => $course?->enrollments()->count() ?? 0,
                    'total_lessons' => $course?->totalLessons() ?? 0,
                    'is_published' => $course?->isPublished() ?? false,
                ];
            });

        return view('livewire.lojista.ava.curso-index', [
            'cursos' => $cursos,
        ])->layout('lojista.layouts.app', ['title' => 'Meus Cursos']);
    }
}
