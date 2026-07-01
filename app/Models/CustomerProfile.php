<?php

namespace App\Models;

use App\Enums\MarketplaceStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerProfile extends Model
{
    protected $fillable = [
        'user_id',
        'marketplace_status',
        'marketing_opt_in',
        'marketing_opt_in_at',
    ];

    protected function casts(): array
    {
        return [
            'marketplace_status'  => MarketplaceStatus::class,
            'marketing_opt_in'    => 'boolean',
            'marketing_opt_in_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isActive(): bool
    {
        return $this->marketplace_status === MarketplaceStatus::Active;
    }
}
