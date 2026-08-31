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

        $cursos = Product::whereHas('offers', fn ($o) => $o->where('expositor_id', $expositor->id))
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
        // Publicar é ato sobre conteúdo canônico, e não sobre a oferta: a
        // autoridade é a mesma da `ProductPolicy` (D-02G-6). Ter uma oferta
        // sobre o item não basta — e nunca deveria ter bastado.
        $curso = AvaCourse::with('product')->findOrFail($course);

        abort_unless(
            $request->user()->can('updateCanonical', $curso->product),
            404,
        );

        $curso->update(['published_at' => $curso->isPublished() ? null : now()]);

        return response()->json([
            'is_published' => $curso->fresh()->isPublished(),
        ]);
    }
}
