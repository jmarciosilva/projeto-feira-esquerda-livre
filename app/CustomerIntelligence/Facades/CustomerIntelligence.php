<?php

namespace App\CustomerIntelligence\Facades;

use App\CustomerIntelligence\Enums\EventName;
use App\CustomerIntelligence\Models\TrackedEvent;
use App\CustomerIntelligence\Models\VisitorSession;
use App\CustomerIntelligence\Services\CustomerIntelligenceService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Facade;

/**
 * Fachada do modulo interno de Customer Intelligence.
 *
 * Existe para que as chamadas de rastreamento espalhadas pela aplicacao
 * continuem se lendo como sempre se leram — a decisao 6 da auditoria CI-01 foi
 * preservar a forma da chamada, o que tornou a migracao da CI-05 uma troca de
 * `use` mais a substituicao da string pelo enum.
 *
 * @method static void track(EventName $event, array $properties = [], ?Model $entity = null, ?VisitorSession $session = null)
 * @method static TrackedEvent record(EventName $event, array $properties = [], ?Model $entity = null, ?VisitorSession $session = null, ?\App\CustomerIntelligence\Models\Visitor $visitor = null, ?int $userId = null, ?\DateTimeInterface $occurredAt = null)
 *
 * @see CustomerIntelligenceService
 */
class CustomerIntelligence extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return CustomerIntelligenceService::class;
    }
}
