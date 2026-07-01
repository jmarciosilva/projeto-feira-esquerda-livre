<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Services\ProductShareImageService;
use Illuminate\Http\Response;

class ProductShareImageController extends Controller
{
    public function __invoke(Product $product, ProductShareImageService $service): Response
    {
        abort_unless(auth()->check() && (
            auth()->user()->isEditor()
            || auth()->user()->expositor?->id === $product->expositor_id
        ), 403);

        $image = $service->make($product);
        $filename = $product->slug . '-compartilhar.png';

        return response($image, 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}
