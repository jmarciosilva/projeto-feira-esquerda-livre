<?php

namespace App\Http\Controllers;

use App\Actions\Catalog\Contexto;
use App\Actions\Catalog\ResolveProductOffer;
use App\Models\Product;
use App\Services\ProductShareImageService;
use Illuminate\Http\Response;

class ProductShareImageController extends Controller
{
    public function __invoke(Product $product, ProductShareImageService $service): Response
    {
        // A imagem carrega preço e loja — ou seja, uma oferta.
        //
        // O lojista gera a da própria loja: `pertenceAoExpositorDe()` é a mesma
        // regra de ownership da CAT-DOM-02F, e com ela não há o que escolher.
        $offer = $product->offers()
            ->doExpositor(auth()->user()?->expositor?->id ?? 0)
            ->first();

        // O editor não tem oferta nenhuma e ainda assim precisa do material de
        // divulgação. Antes ele recebia `orderBy('id')->first()` — que hoje dá
        // a resposta certa porque só existe uma, e amanhã publicaria o preço e o
        // nome de uma loja escolhida pela ordem do banco. Agora ele resolve pelo
        // seletor: informa a oferta, ou só passa quando existe uma só (D-02G-2).
        if ($offer === null && auth()->user()?->isEditor()) {
            $offer = app(ResolveProductOffer::class)(
                $product,
                request()->query('product_offer_id'),
                Contexto::Historico,
            );
        }

        abort_unless(auth()->check() && $offer !== null, 403);

        $image = $service->make($offer);
        $filename = $product->slug.'-compartilhar.png';

        return response($image, 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
