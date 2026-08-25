<?php

namespace App\CustomerIntelligence\Queries;

use App\CustomerIntelligence\Models\TrackedEvent;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

/**
 * Listagem de eventos para o painel.
 *
 * Filtros e paginacao acontecem no banco — nada de trazer o historico inteiro
 * para a memoria e recortar depois em PHP.
 */
class EventQuery
{
    /**
     * @param  array{search?:string|null, event_name?:string|null}  $filters
     * @return LengthAwarePaginator<int, TrackedEvent>
     */
    public function paginate(Carbon $from, Carbon $to, array $filters = [], int $perPage = 50): LengthAwarePaginator
    {
        return TrackedEvent::query()
            // Evita N+1 na coluna "Contato": o e-mail sai do usuario ligado ao
            // visitante, ou do usuario do proprio evento.
            ->with(['visitor:id,visitor_uuid,user_id', 'visitor.user:id,email', 'user:id,email'])
            ->whereBetween('occurred_at', [$from, $to])
            ->when(
                filled($filters['event_name'] ?? null),
                fn ($q) => $q->where('event_name', 'like', $filters['event_name'].'%')
            )
            ->when(
                filled($filters['search'] ?? null),
                fn ($q) => $q->whereHas(
                    'visitor',
                    fn ($v) => $v->where('visitor_uuid', 'like', $filters['search'].'%')
                )
            )
            ->orderByDesc('occurred_at')
            ->paginate($perPage);
    }

    /**
     * Timeline de um visitante.
     *
     * @return LengthAwarePaginator<int, TrackedEvent>
     */
    public function forVisitor(int $visitorId, int $perPage = 25): LengthAwarePaginator
    {
        return TrackedEvent::query()
            // Sem isto, `present()` carregaria visitante e usuário evento a
            // evento — o N+1 clássico de uma timeline.
            ->with(['visitor:id,visitor_uuid,user_id', 'visitor.user:id,email', 'user:id,email'])
            ->where('visitor_id', $visitorId)
            ->orderByDesc('occurred_at')
            ->paginate($perPage);
    }

    /**
     * Formato que as views do painel ja consomem. Mantido em array para nao
     * exigir mudanca de layout.
     *
     * @return array<string, mixed>
     */
    public static function present(TrackedEvent $event): array
    {
        return [
            'event_name' => $event->event_name?->value,
            'visitor_id' => $event->visitor?->visitor_uuid ?? '-',
            'contact_email' => $event->visitor?->user?->email ?? $event->user?->email,
            'occurred_at' => $event->occurred_at?->format('d/m/Y H:i'),
            'properties' => $event->properties,
        ];
    }
}
