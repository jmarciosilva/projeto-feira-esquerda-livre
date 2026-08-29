<?php

namespace App\Models;

use App\Enums\PaymentConflictType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Um desencontro entre o dinheiro e o pedido, registrado para ser reconciliado
 * por gente.
 *
 * Não é log: é evidência. A diferença prática é que log se perde na rotação e
 * ninguém consulta, enquanto isto tem chave única, sobrevive ao rollback que o
 * produziu, e resiste à exclusão do pedido (`RESTRICT`).
 *
 * O que esta linha **não** faz é decidir o desfecho. Estornar, reabrir, ou
 * simplesmente devolver o dinheiro pela mesa do financeiro são caminhos
 * diferentes, e nenhum deles pode ser escolhido automaticamente a partir de um
 * webhook.
 */
class PaymentConflict extends Model
{
    protected $fillable = [
        'order_id',
        'provider',
        'type',
        'external_reference',
        'amount',
        'currency',
        'context',
        'resolved_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => PaymentConflictType::class,
            'amount' => 'decimal:2',
            'context' => 'array',
            'resolved_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /** Conflitos que ainda esperam decisão humana. */
    public function scopeAberto(Builder $query): Builder
    {
        return $query->whereNull('resolved_at');
    }
}
