<?php

namespace App\Http\Controllers\Api\V1\Lojista;

use App\Http\Controllers\Controller;
use App\Models\Ava\AvaCourse;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CursoController extends Controller
{
    /** GET /api/v1/lojista/cursos */
    public function index(Request $request): JsonResponse
    {
        $expositor = $request->user()->expositor;

        $cursos = Product::where('expositor_id', $expositor->id)
            ->where('is_digital', true)
            ->with('avaCourse')
            ->orderBy('name')
            ->get()
            ->map(function (Product $product) {
                $course = $product->avaCourse;

                return [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'course_id' => $course?->id,
                    'total_enrollments' => $course?->enrollments()->count() ?? 0,
                    'total_lessons' => $course?->totalLessons() ?? 0,
                    'is_published' => $course?->isPublished() ?? false,
                ];
            });

        return response()->json(['cursos' => $cursos]);
    }

    /** PATCH /api/v1/lojista/cursos/{course}/publicar */
    public function publicar(Request $request, int $course): JsonResponse
    {
        $curso = AvaCourse::whereHas(
            'product',
            fn ($q) => $q->where('expositor_id', $request->user()->expositor->id)
        )->findOrFail($course);

        $curso->update(['published_at' => $curso->isPublished() ? null : now()]);

        return response()->json([
            'is_published' => $curso->fresh()->isPublished(),
        ]);
    }
}
