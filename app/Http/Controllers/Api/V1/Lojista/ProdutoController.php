<?php

namespace App\Http\Controllers\Api\V1\Lojista;

use App\Actions\Catalog\DeleteProductOffer;
use App\Actions\Catalog\SaveProductWithOffer;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Lojista\ProdutoRequest;
use App\Http\Resources\Api\V1\ProductResource;
use App\Models\Ava\AvaCourse;
use App\Models\Product;
use App\Models\ProductFaq;
use App\Models\ProductOffer;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class ProdutoController extends Controller
{
    /** GET /api/v1/lojista/produtos */
    public function index(Request $request): AnonymousResourceCollection
    {
        $offers = ProductOffer::where('expositor_id', $request->user()->expositor->id)
            ->with('product.category')
            ->orderBy('sort_order')
            ->orderByDesc('created_at')
            ->paginate(20);

        // Cada item responde com a oferta DESTE lojista, inclusive quando ela
        // está inativa — é o painel dele, não a vitrine pública. Fixar a
        // relação evita que o resource caia na oferta vigente de outra loja.
        $offers->setCollection(
            $offers->getCollection()->map(
                fn (ProductOffer $offer) => $offer->product->setRelation('ofertaVigente', $offer)
            )
        );

        return ProductResource::collection($offers);
    }

    /** GET /api/v1/lojista/produtos/{product} */
    public function show(Request $request, Product $product): ProductResource
    {
        $offer = $this->authorizeProduct($request, $product);
        $product->load(['category', 'faqs']);

        return ProductResource::daOferta($offer);
    }

    /** POST /api/v1/lojista/produtos */
    public function store(ProdutoRequest $request, ImageService $imageService): ProductResource
    {
        // Upload/compressao de imagem e I/O de disco, nao de banco: fica fora
        // da transacao, que agora vive dentro da SaveProductWithOffer.
        $data = $this->buildData($request, [], $imageService);

        $offer = app(SaveProductWithOffer::class)($data, $request->user()->expositor);

        $this->syncFaqs($offer->product_id, $request->input('faqs', []));
        $this->syncAvaCourse($offer->product, (bool) ($data['is_digital'] ?? false));

        $offer->product->load(['category', 'faqs']);

        return ProductResource::daOferta($offer);
    }

    /**
     * PUT /api/v1/lojista/produtos/{product} (use POST + _method=PUT quando enviar
     * novas imagens, ja que PHP nao popula uploads em requests PUT reais).
     */
    public function update(ProdutoRequest $request, Product $product, ImageService $imageService): ProductResource
    {
        $offer = $this->authorizeProduct($request, $product);

        $data = $this->buildData($request, $product->images ?? [], $imageService, $product);

        $offer = app(SaveProductWithOffer::class)($data, $request->user()->expositor, $offer);

        $this->syncFaqs($offer->product_id, $request->input('faqs', []));
        $this->syncAvaCourse($offer->product, (bool) ($data['is_digital'] ?? false));

        $offer->product->load(['category', 'faqs']);

        return ProductResource::daOferta($offer);
    }

    /**
     * DELETE /api/v1/lojista/produtos/{product}
     *
     * Remove a oferta, nao o item do catalogo: mesma regra do painel. As
     * imagens ficam, porque pertencem ao produto, que continua existindo para
     * quando alguem voltar a oferece-lo.
     */
    public function destroy(Request $request, Product $product): Response
    {
        $offer = $this->authorizeProduct($request, $product);

        // A recusa por reserva ativa sai como 409 pelo `render()` da propria
        // excecao: e negativa comercial, nao falha do servidor.
        app(DeleteProductOffer::class)($offer);

        return response()->noContent();
    }

    /**
     * Devolve a oferta do lojista autenticado sobre este item, ou aborta.
     *
     * Desde a CAT-DOM-01 a pergunta da SEC-02 mudou de alvo: nao e mais "este
     * produto e seu?", e sim "voce tem uma oferta sobre ele?". Item que o
     * lojista nao oferece nao e editavel por ele.
     */
    private function authorizeProduct(Request $request, Product $product): ProductOffer
    {
        $offer = $product->offers()
            ->where('expositor_id', $request->user()->expositor->id)
            ->first();

        abort_if($offer === null, 403);

        return $offer;
    }

    /** @return array<string, mixed> */
    private function buildData(ProdutoRequest $request, array $currentImages, ImageService $imageService, ?Product $product = null): array
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
            'item_type' => $data['item_type'],
            'category_id' => $data['category_id'] ?? null,
            'name' => $data['name'],
            'slug' => $product?->slug ?: Str::slug($data['name']),
            'short_description' => $data['short_description'] ?? null,
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
