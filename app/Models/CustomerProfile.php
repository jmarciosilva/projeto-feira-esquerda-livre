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
    ];

    protected function casts(): array
    {
        return [
            'marketplace_status' => MarketplaceStatus::class,
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
