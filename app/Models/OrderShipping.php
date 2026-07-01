<?php

namespace App\Models;

use App\Enums\ShippingStatus;
use App\Enums\TrackingEventSource;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class OrderShipping extends Model
{
    protected $fillable = [
        'order_id',
        'order_split_id',
        'expositor_id',
        'carrier',
        'service_name',
        'tracking_code',
        'price',
        'estimated_days',
        'status',
        'shipped_at',
        'delivered_at',
    ];

    protected function casts(): array
    {
        return [
            'status'       => ShippingStatus::class,
            'price'        => 'decimal:2',
            'shipped_at'   => 'datetime',
            'delivered_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function split(): BelongsTo
    {
        return $this->belongsTo(OrderSplit::class, 'order_split_id');
    }

    public function expositor(): BelongsTo
    {
        return $this->belongsTo(Expositor::class);
    }

    public function trackingEvents(): HasMany
    {
        return $this->hasMany(OrderTrackingEvent::class)->orderByDesc('happened_at');
    }

    public function latestEvent(): HasOne
    {
        return $this->hasOne(OrderTrackingEvent::class)->latestOfMany('happened_at');
    }

    public function estimatedDeliveryDate(): ?Carbon
    {
        if (! $this->shipped_at || ! $this->estimated_days) {
            return null;
        }

        return $this->shipped_at->copy()->addWeekdays($this->estimated_days);
    }

    public function isDelivered(): bool
    {
        return $this->status === ShippingStatus::Delivered;
    }

    public function isTrackable(): bool
    {
        return ! $this->status->isTerminal() && filled($this->tracking_code);
    }

    public function carrierTrackingUrl(): ?string
    {
        if (blank($this->tracking_code)) {
            return null;
        }

        $code = rawurlencode($this->tracking_code);

        return match (true) {
            str_contains(strtolower((string) $this->carrier), 'correio') =>
                "https://rastreamento.correios.com.br/app/index.php?objetos={$code}",
            str_contains(strtolower((string) $this->carrier), 'jadlog') =>
                "https://jadlog.com.br/jadlog/tracking.jad?cte={$code}",
            str_contains(strtolower((string) $this->carrier), 'sequoia') =>
                "https://www.sequoiabrasil.com.br/rastreamento/?nfe={$code}",
            str_contains(strtolower((string) $this->carrier), 'azul') =>
                "https://www.azulcargo.com.br/rastreamento/?aw_bill={$code}",
            default => "https://google.com/search?q=rastrear+encomenda+{$code}",
        };
    }

    public function addEvent(string $status, string $description, ?string $location = null, TrackingEventSource $source = TrackingEventSource::Manual): OrderTrackingEvent
    {
        return $this->trackingEvents()->create([
            'status'      => $status,
            'description' => $description,
            'location'    => $location,
            'happened_at' => now(),
            'source'      => $source,
        ]);
    }
}
