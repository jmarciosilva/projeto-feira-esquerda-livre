<?php

namespace App\Http\Controllers\Api\V1\Lojista;

use App\Http\Controllers\Controller;
use App\Models\ExpositorImpression;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExposicaoController extends Controller
{
    /** GET /api/v1/lojista/exposicao */
    public function show(Request $request): JsonResponse
    {
        $expositor = $request->user()->expositor;

        if (! $expositor || ! $expositor->is_featured) {
            return response()->json(['on_home' => false]);
        }

        $chartData = ExpositorImpression::where('expositor_id', $expositor->id)
            ->where('rendered_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(rendered_at) as day, COUNT(*) as count')
            ->groupBy('day')
            ->orderBy('day')
            ->get();

        $activeSlot = $expositor->activeSlot();

        return response()->json([
            'on_home' => true,
            'stats' => [
                'total' => $expositor->total_impressions,
                'last_7_days' => $expositor->impressionsLastDays(7),
                'last_30_days' => $expositor->impressionsLastDays(30),
            ],
            'chart' => $chartData,
            'active_slot' => $activeSlot ? [
                'slot_type' => $activeSlot->slot_type,
                'priority' => $activeSlot->priority,
                'active_from' => $activeSlot->active_from,
                'active_until' => $activeSlot->active_until,
            ] : null,
        ]);
    }
}
