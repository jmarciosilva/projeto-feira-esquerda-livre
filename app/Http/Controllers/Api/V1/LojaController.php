<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ExpositorResource;
use App\Http\Resources\Api\V1\ProductResource;
use App\Models\Expositor;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LojaController extends Controller
{
    /** GET /api/v1/lojas — usado pelo carrossel de expositores do app mobile. */
    public function index(): AnonymousResourceCollection
    {
        $expositores = Expositor::where('is_active', true)
            ->orderByDesc('is_featured')
            ->orderBy('name')
            ->paginate(24);

        return ExpositorResource::collection($expositores);
    }

    /** GET /api/v1/lojas/{slug} */
    public function show(string $slug): JsonResponse
    {
        $expositor = Expositor::where('slug', $slug)->where('is_active', true)->firstOrFail();

        $products = Product::where('expositor_id', $expositor->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'expositor' => new ExpositorResource($expositor),
            'products' => ProductResource::collection($products),
        ]);
    }
}
