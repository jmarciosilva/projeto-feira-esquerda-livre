<?php

namespace App\CustomerIntelligence\Services;

use App\CustomerIntelligence\Enums\EventName;
use App\CustomerIntelligence\Models\TrackedEvent;
use App\CustomerIntelligence\Models\Visitor;
use App\CustomerIntelligence\Models\VisitorSession;
use App\CustomerIntelligence\Support\PropertySanitizer;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Porta de entrada do modulo interno de Customer Intelligence.
 *
 * NAO faz HTTP. O modulo interno e arquiteturalmente independente da plataforma
 * remota — grava direto no banco da propria Feira.
 *
 * Estado na fase CI-02: o metodo `record()` funciona e esta coberto por testes,
 * mas NENHUMA chamada da aplicacao aponta para ele ainda. Os sete eventos
 * atuais continuam sendo enviados pelo SDK externo, inalterados. A troca das
 * chamadas e a CI-06.
 *
 * A resolucao automatica de visitante e sessao a partir do cookie e da CI-04;
 * ate la, quem chama informa (ou omite) visitante e sessao explicitamente.
 */
class CustomerIntelligenceService
{
    public function __construct(
        private readonly PropertySanitizer $sanitizer,
    ) {}

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
        ?DateTimeInterface $occurredAt = null,
    ): TrackedEvent {
        $visitor ??= $session?->visitor;

        return TrackedEvent::create([
            'visitor_id' => $visitor?->getKey(),
            'session_id' => $session?->getKey(),
            'user_id' => $this->resolveUserId($visitor),
            'event_name' => $event,
            'event_category' => $event->category(),
            'entity_type' => $entity === null ? null : $entity->getMorphClass(),
            'entity_id' => $entity?->getKey(),
            'properties' => $properties === [] ? null : $this->sanitizer->sanitize($properties),
            'occurred_at' => $occurredAt ?? now(),
        ]);
    }

    /**
     * O visitante ja identificado tem precedencia sobre a sessao HTTP: dentro
     * de um worker de fila nao existe usuario autenticado, e o vinculo
     * registrado no visitante e a informacao confiavel.
     */
    private function resolveUserId(?Visitor $visitor): ?int
    {
        return $visitor?->user_id ?? Auth::id();
    }
}
