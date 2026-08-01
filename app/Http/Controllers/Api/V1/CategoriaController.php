<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CategoriaResource;
use App\Models\ContentCategory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CategoriaController extends Controller
{
    /** GET /api/v1/categorias?eixo=produto */
    public function index(Request $request): AnonymousResourceCollection
    {
        $eixo = $request->input('eixo');

        $categorias = ContentCategory::where('is_active', true)
            ->when($eixo, fn ($q) => $q->where(fn ($q2) => $q2->whereNull('eixo')->orWhere('eixo', $eixo)))
            ->orderBy('name')
            ->get();

        return CategoriaResource::collection($categorias);
    }
}
