<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductQuestion extends Model
{
    protected $fillable = [
        'product_id',
        'user_id',
        'question',
        'answer',
        'answered_at',
        'answered_by',
        'is_visible',
    ];

    protected function casts(): array
    {
        return [
            'answered_at' => 'datetime',
            'is_visible'  => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function answeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'answered_by');
    }

    public function isAnswered(): bool
    {
        return $this->answered_at !== null;
    }

    /** Primeiro nome do perguntador, para exibição pública. */
    public function askerFirstName(): string
    {
        return explode(' ', $this->user->name ?? 'Visitante')[0];
    }
}
