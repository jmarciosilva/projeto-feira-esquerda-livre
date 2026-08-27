<?php

namespace App\Http\Controllers;

use App\Models\Expositor;
use App\Models\ProductOffer;
use App\Services\ProductShareImageService;
use Illuminate\Http\Response;

class ProductSharePreviewController extends Controller
{
    public function __invoke(string $slug, string $productSlug, ProductShareImageService $service): Response
    {
        $expositor = Expositor::where('slug', $slug)->where('is_active', true)->firstOrFail();

        $offer = ProductOffer::whereHas('product', fn ($q) => $q->where('slug', $productSlug))
            ->where('expositor_id', $expositor->id)
            ->vigente()
            ->with(['product', 'expositor'])
            ->firstOrFail();

        return response($service->make($offer), 200, [
            'Content-Type' => 'image/png',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
