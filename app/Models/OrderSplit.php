<?php

namespace App\Models;

use App\Enums\OrderSplitStatus;
use App\Events\OrderSplitConfirmed;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class OrderSplit extends Model
{
    protected $fillable = [
        'order_id',
        'expositor_id',
        'expositor_name',
        'gross_amount',
        'commission_percent',
        'commission_amount',
        'net_amount',
        'status',
        'confirmed_at',
    ];

    protected function casts(): array
    {
        return [
            'status'              => OrderSplitStatus::class,
            'gross_amount'        => 'decimal:2',
            'commission_percent'  => 'decimal:2',
            'commission_amount'   => 'decimal:2',
            'net_amount'          => 'decimal:2',
            'confirmed_at'        => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function expositor(): BelongsTo
    {
        return $this->belongsTo(Expositor::class);
    }

    public function shipping(): HasOne
    {
        return $this->hasOne(OrderShipping::class, 'order_split_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(OrderMessage::class, 'order_split_id')->orderBy('created_at');
    }

    public function confirmar(): void
    {
        $this->update([
            'status'       => OrderSplitStatus::Confirmado,
            'confirmed_at' => now(),
        ]);

        event(new OrderSplitConfirmed($this));
    }
}
