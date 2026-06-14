<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class Event extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'description',
        'state',
        'city',
        'address',
        'latitude',
        'longitude',
        'start_date',
        'end_date',
        'image_path',
        'banner_image_path',
        'registration_url',
        'capacidade_expositores',
        'vagas_restantes',
        'is_active',
        'is_featured',
    ];

    protected function casts(): array
    {
        return [
            'start_date'             => 'datetime',
            'end_date'               => 'datetime',
            'latitude'               => 'float',
            'longitude'              => 'float',
            'is_active'              => 'boolean',
            'is_featured'            => 'boolean',
            'capacidade_expositores' => 'integer',
            'vagas_restantes'        => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Event $event) {
            if (empty($event->slug)) {
                $event->slug = Str::slug($event->title);
            }
        });
    }

    public function expositores(): BelongsToMany
    {
        return $this->belongsToMany(Expositor::class, 'event_expositores')
            ->withPivot('status')
            ->withTimestamps();
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where('start_date', '>=', now())
            ->orderBy('start_date');
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true)->where('is_active', true);
    }

    public function scopeByState(Builder $query, string $state): Builder
    {
        return $query->where('state', strtoupper($state));
    }
}
