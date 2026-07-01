<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderMessage extends Model
{
    const UPDATED_AT = null;

    protected $fillable = [
        'order_split_id',
        'sender_id',
        'body',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'read_at'    => 'datetime',
            'created_at' => 'datetime',
        ];
    }

    public function split(): BelongsTo
    {
        return $this->belongsTo(OrderSplit::class, 'order_split_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function isFromMe(): bool
    {
        return $this->sender_id === auth()->id();
    }
}
