<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\EventoResource;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AgendaController extends Controller
{
    /** GET /api/v1/agenda */
    public function index(Request $request): AnonymousResourceCollection
    {
        $estado = strtoupper((string) $request->input('estado', ''));
        $mes = (int) $request->input('mes', 0);
        $ano = (int) $request->input('ano', 0);

        $events = Event::where('is_active', true)
            ->where('start_date', '>=', now())
            ->when($estado, fn ($q) => $q->where('state', $estado))
            ->when($mes, fn ($q) => $q->whereMonth('start_date', $mes))
            ->when($ano, fn ($q) => $q->whereYear('start_date', $ano))
            ->orderBy('start_date')
            ->paginate(12);

        return EventoResource::collection($events);
    }

    /** GET /api/v1/agenda/{slug} */
    public function show(string $slug): EventoResource
    {
        $event = Event::where('slug', $slug)->where('is_active', true)->firstOrFail();
        $event->load(['expositores' => fn ($q) => $q->where('event_expositores.status', 'confirmado')]);

        return new EventoResource($event);
    }
}
