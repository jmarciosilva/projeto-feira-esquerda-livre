<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreProductQuestionRequest;
use App\Http\Resources\Api\V1\ProductQuestionResource;
use App\Models\Product;
use App\Models\ProductQuestion;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductQuestionController extends Controller
{
    /** GET /api/v1/produtos/{product}/perguntas */
    public function index(Product $product): AnonymousResourceCollection
    {
        $questions = $product->questions()
            ->where('is_visible', true)
            ->whereNotNull('answered_at')
            ->with('user')
            ->get();

        return ProductQuestionResource::collection($questions);
    }

    /**
     * POST /api/v1/produtos/{product}/perguntas
     *
     * A oferta vem do request (`product_offer_id`, opcional) ou da cardinalidade
     * determinística. Quando nenhuma das duas resolve, o `FormRequest` já
     * recusou com 422 — este método nunca escolhe uma oferta por conta própria.
     */
    public function store(StoreProductQuestionRequest $request, Product $product): ProductQuestionResource
    {
        $question = ProductQuestion::create([
            'product_id' => $product->id,
            'product_offer_id' => $request->resolverOferta()?->id,
            'user_id' => $request->user()->id,
            'question' => $request->validated('question'),
        ]);

        return new ProductQuestionResource($question->load('user'));
    }
}
