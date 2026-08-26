<?php

namespace App\CustomerIntelligence\Actions;

use App\CustomerIntelligence\Models\TrackedEvent;
use App\CustomerIntelligence\Models\Visitor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Desliga o vinculo entre uma pessoa e o rastro comportamental dela.
 *
 * Existe porque as foreign keys `nullOnDelete` so agem quando a conta e
 * excluida. Um titular pode pedir a eliminacao do rastro sem querer encerrar a
 * conta — e e esse caso que esta Action atende.
 *
 * O que faz:
 *
 *   ci_visitors.user_id  → null   desfaz a identificacao
 *   ci_events.user_id    → null   desfaz a identificacao evento a evento
 *   ci_visitors.visitor_uuid → novo   quebra a ponte entre o cookie que ainda
 *                                     esta no navegador e o historico gravado
 *
 * O que NAO faz, de proposito: apagar eventos ou agregados. Depois da
 * desvinculacao os registros nao identificam mais a pessoa — sao contagens sob
 * um pseudonimo que ninguem mais consegue relacionar a ela —, e apagar
 * `ci_daily_metrics` por pedido individual destruiria series historicas de
 * outras pessoas, ja que o agregado nao tem granularidade individual.
 */
class ForgetUser
{
    /**
     * @return array{visitors:int, events:int} quanto foi desvinculado
     */
    public function __invoke(int $userId): array
    {
        return DB::transaction(function () use ($userId) {
            $visitantes = Visitor::where('user_id', $userId)->get();

            foreach ($visitantes as $visitante) {
                $visitante->forceFill([
                    'user_id' => null,
                    'visitor_uuid' => (string) Str::orderedUuid(),
                ])->save();
            }

            $eventos = TrackedEvent::where('user_id', $userId)->update(['user_id' => null]);

            return [
                'visitors' => $visitantes->count(),
                'events' => $eventos,
            ];
        });
    }
}
