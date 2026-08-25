<?php

namespace App\CustomerIntelligence\Models;

use App\CustomerIntelligence\Enums\EventName;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Um fato comportamental gravado pelo modulo interno.
 *
 * Chamado de TrackedEvent, e nao Event, porque `App\Models\Event` ja existe no
 * projeto e representa uma feira da agenda — coisa completamente diferente.
 *
 * Registro append-only: nunca editado depois de gravado. Por isso `UPDATED_AT`
 * e nulo e a tabela nao tem a coluna correspondente.
 */
class TrackedEvent extends Model
{
    use HasUuids;

    public const UPDATED_AT = null;

    protected $table = 'ci_events';

    protected $fillable = [
        'event_uuid',
        'visitor_id',
        'session_id',
        'user_id',
        'event_name',
        'event_category',
        'entity_type',
        'entity_id',
        'properties',
        'occurred_at',
    ];

    /**
     * @return list<string>
     */
    public function uniqueIds(): array
    {
        return ['event_uuid'];
    }

    protected function casts(): array
    {
        return [
            'event_name' => EventName::class,
            'properties' => 'array',
            'occurred_at' => 'datetime',
        ];
    }

    public function visitor(): BelongsTo
    {
        return $this->belongsTo(Visitor::class, 'visitor_id');
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(VisitorSession::class, 'session_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Entidade de dominio a que o evento se refere (Product, Order, Expositor).
     */
    public function entity(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'entity_type', 'entity_id');
    }
}
