<?php

namespace App\Http\Controllers\Api\V1\Lojista;

use App\Actions\Catalog\DeleteProductOffer;
use App\Actions\Catalog\SaveProductWithOffer;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Lojista\ProdutoRequest;
use App\Http\Resources\Api\V1\ProductResource;
use App\Models\Ava\AvaCourse;
use App\Models\Product;
use App\Models\ProductOffer;
use App\Models\ProductOfferFaq;
use App\Services\ImageService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
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
        $product->load('category');
        $offer->load('offerFaqs');

        return ProductResource::daOferta($offer);
    }

    /** POST /api/v1/lojista/produtos */
    public function store(ProdutoRequest $request, ImageService $imageService): ProductResource
    {
        // Upload/compressao de imagem e I/O de disco, nao de banco: fica fora
        // da transacao, que agora vive dentro da SaveProductWithOffer.
        $data = $this->buildData($request, [], $imageService);

        $offer = app(SaveProductWithOffer::class)(
            $data,
            $request->user()->expositor,
            null,
            $request->user(),
        );

        $this->syncFaqs($offer, $request->input('faqs', []));
        $this->syncAvaCourse($offer->product, (bool) ($data['is_digital'] ?? false));

        $offer->product->load('category');
        $offer->load('offerFaqs');

        return ProductResource::daOferta($offer);
    }

    /**
     * PUT /api/v1/lojista/produtos/{product} (use POST + _method=PUT quando enviar
     * novas imagens, ja que PHP nao popula uploads em requests PUT reais).
     */
    public function update(ProdutoRequest $request, Product $product, ImageService $imageService): ProductResource
    {
        $offer = $this->authorizeProduct($request, $product);

        $data = $this->buildData($request, $offer->images ?? [], $imageService, $product);

        // O ator vai explicito: a autoridade canonica e da pessoa, e a action
        // nao deve ter de adivinha-la a partir da sessao.
        $offer = app(SaveProductWithOffer::class)(
            $data,
            $request->user()->expositor,
            $offer,
            $request->user(),
        );

        // Sem default: num update, `faqs` ausente significa "nao mexi nisso",
        // e nao "apague todas". Ver `syncFaqs()`.
        $this->syncFaqs($offer, $request->input('faqs'));
        $this->syncAvaCourse($offer->product, (bool) ($data['is_digital'] ?? false));

        $offer->product->load('category');
        $offer->load('offerFaqs');

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
            // Campo da oferta desde a CAT-DOM-02E; `image_path` saiu do payload
            // porque é espelho legado do medium canônico (D-1), e o que o
            // lojista envia é a imagem da oferta dele.
            'images' => $images,
        ];
    }

    private function syncAvaCourse(Product $product, bool $isDigital): void
    {
        if ($isDigital && ! $product->avaCourse) {
            AvaCourse::create(['product_id' => $product->id]);
        }
    }

    /**
     * Substitui as FAQs do item pela lista recebida — quando houver lista.
     *
     * A sincronizacao comeca apagando tudo, e e isso que tornava a omissao
     * destrutiva: um `PUT` que so mudasse o preco chegava aqui com um array
     * vazio vindo do default e apagava as perguntas frequentes que o lojista
     * havia escrito, sem nunca ter pedido isso. O painel Livewire nao sofria do
     * mesmo problema porque `$this->faqs` e estado carregado no `mount()`, e
     * sempre chega completo.
     *
     * A correcao esta em distinguir os dois casos que o default confundia:
     *
     * - `null` — a chave nao veio no payload. O cliente nao falou de FAQ, e
     *   nada acontece com as FAQs. E o caso do app que atualiza um campo so.
     * - `[]` — a chave veio vazia. E uma frase completa: "nao quero nenhuma".
     *   Apaga todas, como sempre apagou.
     *
     * `faqs: null` explicito cai no primeiro caso. A validacao aceita o valor
     * (`nullable`), e entre preservar e destruir sobre uma intencao ambigua, a
     * unica escolha reversivel e preservar: o unico pedido inequivoco de apagar
     * e a lista vazia.
     *
     * ## O destino mudou na CAT-DOM-02E
     *
     * A FAQ que o lojista escreve e da **oferta** dele, e vai para
     * `product_offer_faqs`. `product_faqs` passou a significar FAQ canonica e
     * nao e mais escrita por aqui: povoa-la a partir do formulario do vendedor
     * faria a plataforma afirmar como verdade do catalogo o que e resposta de
     * um comerciante (D-CAT-16, D-CAT-18).
     *
     * A transacao e nova e necessaria: `product_offer_faqs` tem
     * `UNIQUE(product_offer_id, sort_order)`, e um conjunto meio apagado
     * deixaria a proxima insercao colidindo.
     *
     * @param  array<int, array{question?: string, answer?: string}>|null  $faqs
     */
    private function syncFaqs(ProductOffer $offer, ?array $faqs): void
    {
        if ($faqs === null) {
            return;
        }

        $valid = array_values(array_filter(
            $faqs,
            fn ($f) => ! empty(trim($f['question'] ?? '')) && ! empty(trim($f['answer'] ?? ''))
        ));

        DB::transaction(function () use ($offer, $valid) {
            ProductOfferFaq::where('product_offer_id', $offer->id)->delete();

            foreach ($valid as $i => $faq) {
                ProductOfferFaq::create([
                    'product_offer_id' => $offer->id,
                    'question' => trim($faq['question']),
                    'answer' => trim($faq['answer']),
                    'sort_order' => $i,
                ]);
            }
        });
    }
}
