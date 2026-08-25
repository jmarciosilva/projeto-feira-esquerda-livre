<?php

namespace App\CustomerIntelligence\Services;

use App\CustomerIntelligence\Enums\EventName;
use App\CustomerIntelligence\Jobs\TrackCustomerEventJob;
use App\CustomerIntelligence\Models\TrackedEvent;
use App\CustomerIntelligence\Models\Visitor;
use App\CustomerIntelligence\Models\VisitorSession;
use App\CustomerIntelligence\Support\PropertySanitizer;
use App\CustomerIntelligence\Support\VisitorContext;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * Porta de entrada do modulo interno de Customer Intelligence.
 *
 * NAO faz HTTP. O modulo interno e arquiteturalmente independente da plataforma
 * remota — grava direto no banco da propria Feira.
 *
 * Dois caminhos de escrita:
 *
 *   track()   enfileira o evento e devolve o controle na hora — e o que a
 *             aplicacao deve usar, para nao pagar a gravacao na requisicao.
 *   record()  grava imediatamente. Usado pelo job e por quem precisa do
 *             registro de volta.
 *
 * Estado na fase CI-04: os dois caminhos funcionam e estao cobertos por testes,
 * mas NENHUMA chamada da aplicacao aponta para eles. Os sete eventos atuais
 * continuam sendo enviados pelo SDK externo, sem escrita dupla. A troca das
 * chamadas e a CI-05.
 */
class CustomerIntelligenceService
{
    public function __construct(
        private readonly PropertySanitizer $sanitizer,
        private readonly VisitorContext $context,
    ) {}

    /**
     * Enfileira a gravacao de um evento na fila propria do modulo.
     *
     * Sessao, usuario autenticado e instante do fato sao capturados agora e
     * viajam com o job: dentro do worker nao existe cookie nem usuario logado
     * para consultar.
     *
     * @param  array<string, mixed>  $properties
     */
    public function track(
        EventName $event,
        array $properties = [],
        ?Model $entity = null,
        ?VisitorSession $session = null,
    ): void {
        $session ??= $this->context->session();

        TrackCustomerEventJob::dispatch(
            $event,
            $properties,
            $entity,
            $session,
            $session?->visitor?->user_id ?? Auth::id(),
            Carbon::now(),
        );
    }

    /**
     * Grava um evento comportamental imediatamente.
     *
     * @param  array<string, mixed>  $properties
     */
    public function record(
        EventName $event,
        array $properties = [],
        ?Model $entity = null,
        ?VisitorSession $session = null,
        ?Visitor $visitor = null,
        ?int $userId = null,
        ?DateTimeInterface $occurredAt = null,
    ): TrackedEvent {
        // Sem visitante/sessao explicitos, usa o que o middleware resolveu para
        // esta requisicao. Fora do ciclo HTTP o contexto esta vazio e o evento e
        // gravado sem vinculo, em vez de descartado.
        $session ??= $this->context->session();
        $visitor ??= $session?->visitor;

        return TrackedEvent::create([
            'visitor_id' => $visitor?->getKey(),
            'session_id' => $session?->getKey(),
            'user_id' => $this->resolveUserId($userId, $visitor),
            'event_name' => $event,
            'event_category' => $event->category(),
            'entity_type' => $entity === null ? null : $entity->getMorphClass(),
            'entity_id' => $entity?->getKey(),
            'properties' => $properties === [] ? null : $this->sanitizer->sanitize($properties),
            'occurred_at' => $occurredAt ?? now(),
        ]);
    }

    /**
     * Precedencia: o usuario capturado no despacho vence, porque foi apurado
     * quando a requisicao ainda existia — o job pode rodar depois do logout.
     * Sem ele, vale o vinculo registrado no visitante e, por ultimo, a sessao
     * HTTP atual (que dentro de um worker simplesmente nao existe).
     */
    private function resolveUserId(?int $userId, ?Visitor $visitor): ?int
    {
        return $userId ?? $visitor?->user_id ?? Auth::id();
    }
}
