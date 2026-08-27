<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Expositor extends Model
{
    use HasFactory;

    protected $table = 'expositores';

    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'description',
        'eixos',
        'logo_path',
        'image_path',
        'zipcode',
        'street',
        'number',
        'district',
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
        'home_rotation_weight',
        'total_impressions',
    ];

    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'eixos' => 'array',
            'home_rotation_weight' => 'integer',
            'total_impressions' => 'integer',
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

    /**
     * @deprecated CAT-DOM-01 — o vínculo do expositor com o catálogo passou a
     * ser a oferta. Mantida enquanto `products.expositor_id` existir (dívida
     * D-1); use `offers()` em código novo.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /** As ofertas comerciais desta loja. */
    public function offers(): HasMany
    {
        return $this->hasMany(ProductOffer::class);
    }

    public function orderSplits(): HasMany
    {
        return $this->hasMany(OrderSplit::class);
    }

    public function feedPosts(): HasMany
    {
        return $this->hasMany(FeedPost::class);
    }

    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class, 'event_expositores')
            ->withPivot('status')
            ->withTimestamps();
    }

    public function visibilitySlots(): HasMany
    {
        return $this->hasMany(ExpositorVisibilitySlot::class);
    }

    public function activeSlot(): ?ExpositorVisibilitySlot
    {
        return $this->visibilitySlots()
            ->where(function ($q) {
                $q->whereNull('active_from')->orWhere('active_from', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('active_until')->orWhere('active_until', '>=', now());
            })
            ->orderByDesc('priority')
            ->first();
    }

    public function impressions(): HasMany
    {
        return $this->hasMany(ExpositorImpression::class);
    }

    public function impressionsLastDays(int $days): int
    {
        return $this->impressions()
            ->where('rendered_at', '>=', now()->subDays($days))
            ->count();
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
