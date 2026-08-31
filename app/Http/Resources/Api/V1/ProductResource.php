<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Product;
use App\Models\ProductOffer;
use App\Support\PublicUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Product
 *
 * O JSON não mudou de forma na CAT-DOM-01 — `price`, `stock_quantity`,
 * dimensões e `expositor` continuam onde estavam, e o app mobile não precisa
 * de versão nova. O que mudou é de onde eles vêm: da oferta, não do produto.
 *
 * Quando a resposta trata de uma loja específica (vitrine do expositor, item do
 * carrinho), a oferta é fixada com `daOferta()`. Sem isso, vale a oferta
 * vigente do item — hoje sempre única, porque o backfill foi 1:1.
 */
class ProductResource extends JsonResource
{
    private ?ProductOffer $ofertaFixada = null;

    public static function daOferta(ProductOffer $offer): self
    {
        $resource = new self($offer->product);
        $resource->ofertaFixada = $offer;

        return $resource;
    }

    public function toArray(Request $request): array
    {
        $oferta = $this->oferta();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'item_type' => $this->item_type?->value,
            'short_description' => $this->short_description,
            'description' => $this->description,
            'price' => (float) $oferta?->price,
            'price_type' => $oferta?->price_type?->value,
            'modality' => $oferta?->modality?->value,
            'duration_min' => $oferta?->duration_min,
            'has_stock' => (bool) $oferta?->has_stock,
            'stock_quantity' => $oferta?->stock_quantity,
            'is_digital' => (bool) $this->is_digital,
            'is_featured' => (bool) $oferta?->is_featured,
            'is_active' => (bool) $oferta?->is_active,
            'weight' => $oferta?->weight ? (float) $oferta->weight : null,
            'height' => $oferta?->height ? (float) $oferta->height : null,
            'width' => $oferta?->width ? (float) $oferta->width : null,
            'length' => $oferta?->length ? (float) $oferta->length : null,
            // CAT-DOM-02E: o resource ja representa o item **exibido por uma
            // oferta** — preco, estoque e dimensoes vem dela desde a 01. A
            // imagem passou a vir do mesmo lugar, com fallback canonico.
            'main_image_url' => $oferta?->urlDaImagemPrincipal('medium') ?? $this->main_image_url,
            'images' => collect($oferta?->imagensParaExibicao() ?? [])->map(fn (array $image) => [
                'thumbnail_url' => isset($image['thumb']) ? PublicUrl::for($image['thumb']) : null,
                'medium_url' => isset($image['medium']) ? PublicUrl::for($image['medium']) : null,
            ])->values(),
            'expositor' => $this->when(
                $oferta?->relationLoaded('expositor') || $this->relationLoaded('expositor'),
                fn () => new ExpositorResource($oferta?->expositor ?? $this->expositor)
            ),
            'category' => $this->whenLoaded('category', fn () => $this->category ? [
                'id' => $this->category->id,
                'name' => $this->category->name,
                'slug' => $this->category->slug,
            ] : null),
            // A FAQ exposta e a comercial, da oferta. `product_faqs` virou FAQ
            // canonica na 02E e nao entra aqui — nem misturada, nem como
            // fallback (D-CAT-16).
            'faqs' => $this->when(
                (bool) $oferta?->relationLoaded('offerFaqs'),
                fn () => $oferta->offerFaqs->map(fn ($faq) => [
                    'question' => $faq->question,
                    'answer' => $faq->answer,
                ]),
            ),
            'created_at' => $this->created_at,
        ];
    }

    private function oferta(): ?ProductOffer
    {
        return $this->ofertaFixada ?? $this->resource->ofertaVigente;
    }
}
