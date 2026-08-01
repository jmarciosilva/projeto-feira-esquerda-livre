<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\RastreioResource;
use App\Models\OrderShipping;

class RastreioController extends Controller
{
    /** GET /api/v1/rastreio/{trackingCode} */
    public function show(string $trackingCode): RastreioResource
    {
        $shipping = OrderShipping::where('tracking_code', strtoupper($trackingCode))
            ->with(['expositor', 'trackingEvents'])
            ->firstOrFail();

        return new RastreioResource($shipping);
    }
}
