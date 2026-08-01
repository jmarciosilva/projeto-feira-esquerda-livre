<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\ItemType;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CatalogoController extends Controller
{
    /** GET /api/v1/{produtos|servicos|cuidados} */
    public function index(Request $request, string $tipo): AnonymousResourceCollection
    {
        $eixo = ItemType::from($tipo);
        $busca = $request->input('busca', '');
        $categoriaId = (int) $request->input('categoria', 0);

        $items = Product::doEixo($eixo)
            ->with('expositor')
            ->when($busca, fn ($q) => $q->where('name', 'like', "%{$busca}%"))
            ->when($categoriaId, fn ($q) => $q->where('category_id', $categoriaId))
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->paginate(24);

        return ProductResource::collection($items);
    }

    /** GET /api/v1/produtos/{product} */
    public function show(Product $product): ProductResource
    {
        abort_unless($product->is_active, 404);

        return new ProductResource($product->load(['expositor', 'category', 'faqs']));
    }
}
