<?php

namespace App\CustomerIntelligence\Queries;

use App\CustomerIntelligence\Models\Visitor;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

/**
 * Listagem e detalhe de visitantes.
 *
 * A tela historicamente se chama "Contatos", mas o dado sempre foi visitante:
 * `identify()` nunca chegou a ser usado no projeto. Nome, e-mail e telefone
 * continuam vivendo apenas em `users` e sao alcancados pela relacao — nada e
 * duplicado aqui.
 */
class VisitorQuery
{
    /**
     * @param  array{search?:string|null}  $filters
     * @return LengthAwarePaginator<int, Visitor>
     */
    public function paginate(Carbon $from, Carbon $to, array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        return Visitor::query()
            ->with('user:id,name,email')
            ->withCount(['events as events_count' => fn ($q) => $q->whereBetween('occurred_at', [$from, $to])])
            ->whereBetween('last_seen_at', [$from, $to])
            ->when(
                filled($filters['search'] ?? null),
                fn ($q) => $q->where(function ($w) use ($filters) {
                    $w->where('visitor_uuid', 'like', $filters['search'].'%')
                        ->orWhereHas('user', fn ($u) => $u->where('email', 'like', '%'.$filters['search'].'%'));
                })
            )
            ->orderByDesc('last_seen_at')
            ->paginate($perPage);
    }

    public function find(string $visitorUuid): ?Visitor
    {
        return Visitor::query()
            ->with('user:id,name,email')
            ->withCount(['events as events_count', 'sessions as sessions_count'])
            ->where('visitor_uuid', $visitorUuid)
            ->first();
    }

    /**
     * Formato consumido pelas views do painel.
     *
     * `lead_score` NAO existe no modulo interno: vinha do CRM da plataforma
     * remota, que nunca recebeu um `identify()`. Em seu lugar a interface passou
     * a mostrar a contagem de eventos do visitante, que e um dado real.
     *
     * @return array<string, mixed>
     */
    public static function present(Visitor $visitor): array
    {
        return [
            'visitor_uuid' => $visitor->visitor_uuid,
            'email' => $visitor->user?->email,
            'name' => $visitor->user?->name,
            'events_count' => (int) ($visitor->events_count ?? 0),
            'sessions_count' => (int) ($visitor->sessions_count ?? 0),
            'created_at' => $visitor->first_seen_at?->format('d/m/Y H:i'),
            'last_event_at' => $visitor->last_seen_at?->format('d/m/Y H:i'),
        ];
    }
}
