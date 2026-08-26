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
 * Acucar para os pontos onde nao ha construtor para injetar o service —
 * closures de rota, componentes Livewire, listeners. Quem tem construtor pode
 * resolver CustomerIntelligenceService diretamente.
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
