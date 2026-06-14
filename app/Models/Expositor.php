<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Expositor extends Model
{
    protected $table = 'expositores';

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'description',
        'eixos',
        'logo_path',
        'image_path',
        'city',
        'state',
        'whatsapp',
        'email',
        'website_url',
        'instagram_url',
        'facebook_url',
        'banco_nome',
        'banco_agencia',
        'banco_conta',
        'banco_tipo_conta',
        'pix_tipo',
        'pix_chave',
        'is_featured',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'is_active'   => 'boolean',
            'eixos'       => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Expositor $expositor) {
            if (empty($expositor->slug)) {
                $expositor->slug = Str::slug($expositor->name);
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class, 'event_expositores')
            ->withPivot('status')
            ->withTimestamps();
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true)
            ->where('is_active', true)
            ->orderBy('sort_order');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }
}
