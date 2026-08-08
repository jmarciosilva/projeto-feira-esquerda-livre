<?php

namespace App\Models;

use App\Enums\ItemType;
use App\Enums\Modality;
use App\Enums\PriceType;
use App\Models\Ava\AvaCourse;
use App\Support\PublicUrl;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Product extends Model
{
    protected $fillable = [
        'expositor_id',
        'category_id',
        'item_type',
        'name',
        'slug',
        'description',
        'image_path',
        'images',
        'price',
        'weight',
        'height',
        'width',
        'length',
        'price_type',
        'modality',
        'duration_min',
        'has_stock',
        'stock_quantity',
        'is_featured',
        'is_active',
        'is_digital',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'item_type' => ItemType::class,
            'price_type' => PriceType::class,
            'modality' => Modality::class,
            'price' => 'decimal:2',
            'weight' => 'decimal:3',
            'height' => 'decimal:2',
            'width' => 'decimal:2',
            'length' => 'decimal:2',
            'has_stock' => 'boolean',
            'is_featured' => 'boolean',
            'is_active' => 'boolean',
            'is_digital' => 'boolean',
            'images' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Product $product) {
            if (empty($product->slug)) {
                $product->slug = Str::slug($product->name);
            }
        });
    }

    public function expositor(): BelongsTo
    {
        return $this->belongsTo(Expositor::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ContentCategory::class);
    }

    public function faqs(): HasMany
    {
        return $this->hasMany(ProductFaq::class)->orderBy('sort_order');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(ProductQuestion::class)->orderByDesc('created_at');
    }

    public function avaCourse(): HasOne
    {
        return $this->hasOne(AvaCourse::class);
    }

    public function isDigital(): bool
    {
        return (bool) $this->is_digital;
    }

    /** Retorna a URL da primeira imagem médio, ou image_path legado. */
    public function getMainImageUrlAttribute(): ?string
    {
        $images = $this->images;
        if (! empty($images[0]['medium'])) {
            return PublicUrl::for($images[0]['medium']);
        }
        if ($this->image_path) {
            return PublicUrl::for($this->image_path);
        }

        return null;
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

    public function scopeDoEixo(Builder $query, ItemType $type): Builder
    {
        return $query->where('item_type', $type->value)->where('is_active', true);
    }

    public function isProduto(): bool
    {
        return $this->item_type === ItemType::Produto;
    }

    public function isServico(): bool
    {
        return $this->item_type === ItemType::Servico;
    }

    public function isCuidado(): bool
    {
        return $this->item_type === ItemType::Cuidado;
    }
}
