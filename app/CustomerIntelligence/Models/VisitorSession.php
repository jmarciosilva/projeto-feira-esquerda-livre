<?php

namespace App\CustomerIntelligence\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Janela de navegacao de um visitante.
 *
 * Chamada de VisitorSession, e nao Session, para nao competir com a sessao do
 * proprio Laravel — que neste projeto tambem vive no banco.
 */
class VisitorSession extends Model
{
    use HasUuids;

    protected $table = 'ci_sessions';

    protected $fillable = [
        'session_uuid',
        'visitor_id',
        'started_at',
        'last_activity_at',
        'ended_at',
        'landing_url',
        'referrer',
        'utm_source',
        'utm_medium',
        'utm_campaign',
    ];

    /**
     * @return list<string>
     */
    public function uniqueIds(): array
    {
        return ['session_uuid'];
    }

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'last_activity_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function visitor(): BelongsTo
    {
        return $this->belongsTo(Visitor::class, 'visitor_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(TrackedEvent::class, 'session_id');
    }

    public function isOpen(): bool
    {
        return $this->ended_at === null;
    }
}
