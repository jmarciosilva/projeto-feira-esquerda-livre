<?php

namespace App\Http\Controllers\Api\V1\Lojista;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Lojista\ProdutoRequest;
use App\Http\Resources\Api\V1\ProductResource;
use App\Models\Ava\AvaCourse;
use App\Models\Product;
use App\Models\ProductFaq;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProdutoController extends Controller
{
    /** GET /api/v1/lojista/produtos */
    public function index(Request $request): AnonymousResourceCollection
    {
        $products = Product::where('expositor_id', $request->user()->expositor->id)
            ->with('category')
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->paginate(20);

        return ProductResource::collection($products);
    }

    /** GET /api/v1/lojista/produtos/{product} */
    public function show(Request $request, Product $product): ProductResource
    {
        $this->authorizeProduct($request, $product);

        return new ProductResource($product->load(['category', 'faqs']));
    }

    /** POST /api/v1/lojista/produtos */
    public function store(ProdutoRequest $request, ImageService $imageService): ProductResource
    {
        // Upload/compressão de imagem é I/O de disco, não de banco — fica fora da transação.
        $data = $this->buildData($request, $request->user()->expositor->id, [], $imageService);

        $product = DB::transaction(function () use ($data, $request) {
            $product = Product::create($data);
            $this->syncFaqs($product->id, $request->input('faqs', []));
            $this->syncAvaCourse($product, (bool) ($data['is_digital'] ?? false));

            return $product;
        });

        return new ProductResource($product->load(['category', 'faqs']));
    }

    /**
     * PUT /api/v1/lojista/produtos/{product} (use POST + _method=PUT quando enviar
     * novas imagens, já que PHP não popula uploads em requests PUT reais).
     */
    public function update(ProdutoRequest $request, Product $product, ImageService $imageService): ProductResource
    {
        $this->authorizeProduct($request, $product);

        $data = $this->buildData($request, $product->expositor_id, $product->images ?? [], $imageService, $product);

        DB::transaction(function () use ($product, $data, $request) {
            $product->update($data);
            $this->syncFaqs($product->id, $request->input('faqs', []));
            $this->syncAvaCourse($product, (bool) ($data['is_digital'] ?? false));
        });

        return new ProductResource($product->fresh()->load(['category', 'faqs']));
    }

    /** DELETE /api/v1/lojista/produtos/{product} */
    public function destroy(Request $request, Product $product): \Illuminate\Http\Response
    {
        $this->authorizeProduct($request, $product);

        (new ImageService)->delete(
            collect($product->images ?? [])->flatMap(fn ($image) => [$image['thumb'] ?? null, $image['medium'] ?? null])->filter()->all()
        );

        $product->delete();

        return response()->noContent();
    }

    private function authorizeProduct(Request $request, Product $product): void
    {
        abort_unless($product->expositor_id === $request->user()->expositor->id, 403);
    }

    /** @return array<string, mixed> */
    private function buildData(ProdutoRequest $request, int $expositorId, array $currentImages, ImageService $imageService, ?Product $product = null): array
    {
        $data = $request->validated();
        $isProduto = $data['item_type'] === 'produto';

        // Remove imagens marcadas para exclusão
        foreach ($request->input('remove_image_indexes', []) as $index) {
            if (isset($currentImages[$index])) {
                $imageService->delete([$currentImages[$index]['thumb'] ?? null, $currentImages[$index]['medium'] ?? null]);
                unset($currentImages[$index]);
            }
        }
        $images = array_values($currentImages);

        foreach ($request->file('images', []) as $file) {
            if (count($images) >= 4) {
                break;
            }
            $images[] = $imageService->store($file, 'products');
        }

        return [
            'expositor_id' => $expositorId,
            'item_type' => $data['item_type'],
            'category_id' => $data['category_id'] ?? null,
            'name' => $data['name'],
            'slug' => $product?->slug ?: Str::slug($data['name']),
            'description' => $data['description'] ?? null,
            'price' => $data['price'] ?? null,
            'weight' => $isProduto ? ($data['weight'] ?? null) : null,
            'height' => $isProduto ? ($data['height'] ?? null) : null,
            'width' => $isProduto ? ($data['width'] ?? null) : null,
            'length' => $isProduto ? ($data['length'] ?? null) : null,
            'price_type' => $isProduto ? 'fixo' : ($data['price_type'] ?? null),
            'modality' => $isProduto ? null : ($data['modality'] ?? null),
            'duration_min' => $isProduto ? null : ($data['duration_min'] ?? null),
            'has_stock' => $isProduto && ($data['has_stock'] ?? true),
            'stock_quantity' => ($isProduto && ($data['has_stock'] ?? true)) ? ($data['stock_quantity'] ?? null) : null,
            'is_active' => $data['is_active'] ?? true,
            'is_featured' => $data['is_featured'] ?? false,
            'is_digital' => $data['is_digital'] ?? false,
            'sort_order' => $data['sort_order'] ?? 0,
            'images' => $images,
            'image_path' => $images[0]['medium'] ?? $product?->image_path,
        ];
    }

    private function syncAvaCourse(Product $product, bool $isDigital): void
    {
        if ($isDigital && ! $product->avaCourse) {
            AvaCourse::create(['product_id' => $product->id]);
        }
    }

    private function syncFaqs(int $productId, array $faqs): void
    {
        ProductFaq::where('product_id', $productId)->delete();

        $valid = array_values(array_filter(
            $faqs,
            fn ($f) => ! empty(trim($f['question'] ?? '')) && ! empty(trim($f['answer'] ?? ''))
        ));

        foreach ($valid as $i => $faq) {
            ProductFaq::create([
                'product_id' => $productId,
                'question' => trim($faq['question']),
                'answer' => trim($faq['answer']),
                'sort_order' => $i,
            ]);
        }
    }
}
