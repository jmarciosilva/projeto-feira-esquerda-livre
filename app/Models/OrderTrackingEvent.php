<?php

namespace App\Models;

use App\Enums\TrackingEventSource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderTrackingEvent extends Model
{
    protected $fillable = [
        'order_shipping_id',
        'status',
        'description',
        'location',
        'happened_at',
        'source',
    ];

    protected function casts(): array
    {
        return [
            'happened_at' => 'datetime',
            'source'      => TrackingEventSource::class,
        ];
    }

    public function shipping(): BelongsTo
    {
        return $this->belongsTo(OrderShipping::class, 'order_shipping_id');
    }
}
