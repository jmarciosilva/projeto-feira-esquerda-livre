<?php

namespace App\CustomerIntelligence\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Visitante conhecido pelo modulo de Customer Intelligence.
 *
 * Identidade anonima e persistente. Quando o visitante se autentica, `user_id`
 * passa a apontar para a conta — nome, e-mail e telefone continuam morando
 * apenas em `users`, alcancados pela relacao.
 */
class Visitor extends Model
{
    use HasUuids;

    protected $table = 'ci_visitors';

    protected $fillable = [
        'visitor_uuid',
        'user_id',
        'first_seen_at',
        'last_seen_at',
        'metadata',
    ];

    /**
     * A chave primaria continua sendo o `id` auto incremental; apenas o
     * identificador publico e UUID. Sobrescrever `uniqueIds()` sem incluir a
     * chave primaria mantem `id` como inteiro auto incremental.
     *
     * @return list<string>
     */
    public function uniqueIds(): array
    {
        return ['visitor_uuid'];
    }

    protected function casts(): array
    {
        return [
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(VisitorSession::class, 'visitor_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(TrackedEvent::class, 'visitor_id');
    }

    public function isIdentified(): bool
    {
        return $this->user_id !== null;
    }
}
