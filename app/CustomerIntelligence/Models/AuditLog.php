<?php

namespace App\CustomerIntelligence\Models;

use App\CustomerIntelligence\Enums\AuditAction;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Uma linha da trilha de auditoria administrativa.
 *
 * Append-only, como `TrackedEvent`: `UPDATED_AT` nulo e sem a coluna
 * correspondente. Nao ha metodo de edicao nem de exclusao aqui — a unica
 * remocao prevista e o expurgo por retencao, feito pelo comando proprio.
 *
 * Nao tem nada a ver com `ci_events`, apesar da vizinhanca: auditoria nao e
 * analytics. Esta tabela registra quem OLHOU o comportamento, e por isso
 * jamais pode ser alimentada pelo `CustomerIntelligence::track()` — seria a
 * auditoria virando o objeto que ela audita, e ainda por cima sujeita ao
 * consentimento de quem esta sendo auditado.
 */
class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'ci_audit_logs';

    protected $fillable = [
        'user_id',
        'action',
        'resource_type',
        'resource_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'action' => AuditAction::class,
            'created_at' => 'datetime',
        ];
    }

    /**
     * Quem executou. Nulo em execucao agendada e tambem quando a conta foi
     * removida depois — a acao permanece registrada de qualquer forma.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
