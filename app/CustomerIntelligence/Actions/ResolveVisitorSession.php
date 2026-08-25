<?php

namespace App\CustomerIntelligence\Actions;

use App\CustomerIntelligence\Models\Visitor;
use App\CustomerIntelligence\Models\VisitorSession;
use Illuminate\Support\Carbon;

/**
 * Encontra ou cria o visitante e a sessao correspondentes aos identificadores
 * vindos dos cookies.
 *
 * Vive fora do middleware de proposito: a regra de "quando uma sessao acaba e
 * outra comeca" nao depende de HTTP e fica testavel sem simular requisicao.
 */
class ResolveVisitorSession
{
    /**
     * @param  array<string, string|null>  $origin  landing_url, referrer e UTMs,
     *                                              gravados apenas na abertura da sessao
     */
    public function __invoke(
        string $visitorUuid,
        string $sessionUuid,
        ?int $userId = null,
        array $origin = [],
    ): VisitorSession {
        $now = Carbon::now();

        $visitor = $this->resolveVisitor($visitorUuid, $userId, $now);
        $session = VisitorSession::where('session_uuid', $sessionUuid)->first();

        if ($this->needsNewSession($session, $visitor, $now)) {
            return $this->startSession($visitor, $now, $origin);
        }

        $session->forceFill(['last_activity_at' => $now])->save();
        $session->setRelation('visitor', $visitor);

        return $session;
    }

    private function resolveVisitor(string $visitorUuid, ?int $userId, Carbon $now): Visitor
    {
        $visitor = Visitor::firstOrCreate(
            ['visitor_uuid' => $visitorUuid],
            ['first_seen_at' => $now, 'last_seen_at' => $now],
        );

        $changes = ['last_seen_at' => $now];

        // O vinculo com a conta e gravado uma unica vez, no primeiro acesso
        // autenticado. Nao sobrescrevemos um vinculo existente: dois usuarios
        // no mesmo navegador continuam sendo dois visitantes distintos assim
        // que o segundo receber seu proprio cookie.
        if ($userId !== null && $visitor->user_id === null) {
            $changes['user_id'] = $userId;
        }

        $visitor->forceFill($changes)->save();

        return $visitor;
    }

    /**
     * Uma sessao nova comeca quando nao ha registro, quando o registro pertence
     * a outro visitante (cookies fora de sincronia) ou quando ela ja foi
     * encerrada por inatividade.
     */
    private function needsNewSession(?VisitorSession $session, Visitor $visitor, Carbon $now): bool
    {
        if ($session === null || $session->visitor_id !== $visitor->id) {
            return true;
        }

        if ($session->ended_at !== null) {
            return true;
        }

        $ttl = (int) config('customer-intelligence-internal.session_cookie.minutes', 30);
        $lastActivity = $session->last_activity_at ?? $session->started_at;

        return $lastActivity !== null && $lastActivity->lt($now->copy()->subMinutes($ttl));
    }

    /**
     * @param  array<string, string|null>  $origin
     */
    private function startSession(Visitor $visitor, Carbon $now, array $origin): VisitorSession
    {
        // Fecha o que estiver aberto para este visitante, para nao acumular
        // sessoes eternamente sem `ended_at`.
        VisitorSession::where('visitor_id', $visitor->id)
            ->whereNull('ended_at')
            ->update(['ended_at' => $now, 'updated_at' => $now]);

        $session = VisitorSession::create([
            'visitor_id' => $visitor->id,
            'started_at' => $now,
            'last_activity_at' => $now,
            'landing_url' => $origin['landing_url'] ?? null,
            'referrer' => $origin['referrer'] ?? null,
            'utm_source' => $origin['utm_source'] ?? null,
            'utm_medium' => $origin['utm_medium'] ?? null,
            'utm_campaign' => $origin['utm_campaign'] ?? null,
        ]);

        $session->setRelation('visitor', $visitor);

        return $session;
    }
}
