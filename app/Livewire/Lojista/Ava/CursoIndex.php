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

        $cursos = Product::where('expositor_id', $expositor->id)
            ->where('is_digital', true)
            ->with(['avaCourse'])
            ->orderBy('name')
            ->get()
            ->map(function (Product $product) {
                $course = $product->avaCourse;

                return [
                    'product'          => $product,
                    'course'           => $course,
                    'total_enrollments' => $course?->enrollments()->count() ?? 0,
                    'total_lessons'    => $course?->totalLessons() ?? 0,
                    'is_published'     => $course?->isPublished() ?? false,
                ];
            });

        return view('livewire.lojista.ava.curso-index', [
            'cursos' => $cursos,
        ])->layout('lojista.layouts.app', ['title' => 'Meus Cursos']);
    }
}
