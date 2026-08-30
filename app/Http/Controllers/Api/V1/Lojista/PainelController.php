<?php

namespace App\Http\Controllers\Api\V1\Lojista;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PainelController extends Controller
{
    /** GET /api/v1/lojista/painel */
    public function index(Request $request): JsonResponse
    {
        $expositor = $request->user()->expositor;

        $upcomingEvents = $expositor
            ? $expositor->events()->where('start_date', '>=', now())->orderBy('start_date')->take(5)->get()
            : collect();

        return response()->json([
            // Mesma contagem do painel web, e pela mesma razao: o numero
            // corresponde ao que `GET /lojista/produtos` devolve, e aquele
            // endpoint pagina ofertas. A relacao legada `products` divergia
            // dele depois de qualquer remocao de oferta.
            'total_produtos' => $expositor?->offers()->count() ?? 0,
            'upcoming_events' => $upcomingEvents->map(fn ($event) => [
                'title' => $event->title,
                'slug' => $event->slug,
                'start_date' => $event->start_date,
                'city' => $event->city,
                'state' => $event->state,
            ]),
        ]);
    }
}
