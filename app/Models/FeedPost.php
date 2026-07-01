<?php

namespace App\Models;

use App\Enums\FeedPostType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeedPost extends Model
{
    protected $fillable = [
        'expositor_id',
        'type',
        'content',
        'images',
        'is_visible',
        'reported_count',
    ];

    protected function casts(): array
    {
        return [
            'type' => FeedPostType::class,
            'images' => 'array',
            'is_visible' => 'boolean',
            'reported_count' => 'integer',
        ];
    }

    public function expositor(): BelongsTo
    {
        return $this->belongsTo(Expositor::class);
    }

    public function likes(): HasMany
    {
        return $this->hasMany(FeedLike::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(FeedComment::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(FeedReport::class);
    }

    public function moderationLogs(): HasMany
    {
        return $this->hasMany(FeedModerationLog::class);
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_visible', true);
    }

    public function isLikedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $this->likes->contains('user_id', $user->id);
    }
}
