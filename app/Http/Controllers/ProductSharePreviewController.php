<?php

namespace App\Http\Controllers;

use App\Models\Expositor;
use App\Models\Product;
use App\Services\ProductShareImageService;
use Illuminate\Http\Response;

class ProductSharePreviewController extends Controller
{
    public function __invoke(string $slug, string $productSlug, ProductShareImageService $service): Response
    {
        $expositor = Expositor::where('slug', $slug)->where('is_active', true)->firstOrFail();
        $product = Product::where('expositor_id', $expositor->id)
            ->where('slug', $productSlug)
            ->where('is_active', true)
            ->firstOrFail();

        return response($service->make($product), 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
