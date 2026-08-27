<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\ProductShareImageService;
use Illuminate\Http\Response;

class ProductShareImageController extends Controller
{
    public function __invoke(Product $product, ProductShareImageService $service): Response
    {
        $offer = $product->offers()
            ->where('expositor_id', auth()->user()?->expositor?->id)
            ->first();

        // A imagem carrega preço e loja — ou seja, uma oferta. O lojista gera a
        // da própria loja; o editor, a primeira que existir, porque ele não tem
        // oferta nenhuma e ainda assim precisa do material de divulgação.
        $offer ??= auth()->user()?->isEditor()
            ? $product->offers()->orderBy('id')->first()
            : null;

        abort_unless(auth()->check() && $offer !== null, 403);

        $image = $service->make($offer);
        $filename = $product->slug.'-compartilhar.png';

        return response($image, 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
