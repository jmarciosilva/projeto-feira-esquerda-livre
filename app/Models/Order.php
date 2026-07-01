<?php

namespace App\Models;

use App\Enums\DeliveryType;
use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Order extends Model
{
    protected $fillable = [
        'reference',
        'user_id',
        'session_id',
        'customer_name',
        'customer_whatsapp',
        'customer_email',
        'delivery_type',
        'customer_address_id',
        'address_cep',
        'address_rua',
        'address_numero',
        'address_complemento',
        'address_bairro',
        'address_cidade',
        'address_estado',
        'items_total',
        'shipping_total',
        'shipping_note',
        'total_amount',
        'payment_method',
        'payment_provider',
        'payment_status',
        'mercado_pago_preference_id',
        'mercado_pago_payment_id',
        'mercado_pago_init_point',
        'mercado_pago_sandbox_init_point',
        'payment_payload',
        'status',
        'notes',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'status'         => OrderStatus::class,
            'delivery_type'  => DeliveryType::class,
            'items_total'    => 'decimal:2',
            'shipping_total' => 'decimal:2',
            'total_amount'   => 'decimal:2',
            'payment_payload' => 'array',
            'paid_at'        => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Order $order) {
            if (empty($order->reference)) {
                do {
                    $reference = strtoupper(Str::random(8));
                } while (static::where('reference', $reference)->exists());
                $order->reference = $reference;
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function customerAddress(): BelongsTo
    {
        return $this->belongsTo(CustomerAddress::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function splits(): HasMany
    {
        return $this->hasMany(OrderSplit::class);
    }

    public function shippings(): HasMany
    {
        return $this->hasMany(OrderShipping::class);
    }

    public function scopeStatus(Builder $query, OrderStatus $status): Builder
    {
        return $query->where('status', $status);
    }
}
