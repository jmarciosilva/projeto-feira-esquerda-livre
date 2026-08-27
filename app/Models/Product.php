<?php

namespace App\Models;

use App\Enums\ItemType;
use App\Enums\Modality;
use App\Enums\PriceType;
use App\Models\Ava\AvaCourse;
use App\Support\PublicUrl;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'expositor_id',
        'category_id',
        'item_type',
        'name',
        'slug',
        'short_description',
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

    /**
     * @deprecated CAT-DOM-01 — o produto deixou de ter dono. Quem tem dono é a
     * oferta. Mantida enquanto `products.expositor_id` existir como coluna
     * legada (dívida D-1); nenhuma superfície deve autorizar nada por ela.
     */
    public function expositor(): BelongsTo
    {
        return $this->belongsTo(Expositor::class);
    }

    /** Todos os expositores que oferecem este item, em qualquer status. */
    public function offers(): HasMany
    {
        return $this->hasMany(ProductOffer::class);
    }

    /**
     * A oferta que o público vê quando chega a este item sem passar por uma
     * loja — card do catálogo, destaque da home, resultado de busca.
     *
     * Hoje há sempre no máximo uma oferta vigente por produto, porque o
     * backfill da CAT-DOM-01C foi 1:1 e o cadastro cria produto e oferta
     * juntos. A ordenação por preço não é decoração: ela fixa qual oferta
     * responde no dia em que houver duas, sem que a resposta dependa da ordem
     * de inserção no banco.
     */
    public function ofertaVigente(): HasOne
    {
        return $this->hasOne(ProductOffer::class)
            ->vigente()
            ->orderBy('price')
            ->orderBy('id');
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

    /**
     * Itens que alguém está efetivamente oferecendo agora.
     *
     * É a tradução em consulta da decisão H-1 da CAT-DOM-01B: um produto só
     * aparece nas vitrines enquanto existir ao menos uma oferta vigente. Sem
     * nenhuma, ele continua existindo no catálogo e na memória da Catalog
     * Intelligence — apenas não há quem o venda.
     *
     * A regra de vigência não é repetida aqui: ela vive inteira em
     * `ProductOffer::scopeVigente()`, e é essa concentração que impede o
     * catálogo por eixo e a página da loja de divergirem de novo.
     */
    public function scopeComOfertaVigente(Builder $query, bool $apenasDestaques = false): Builder
    {
        return $query->whereHas('offers', function (Builder $offer) use ($apenasDestaques) {
            $offer->vigente();

            if ($apenasDestaques) {
                $offer->where('is_featured', true);
            }
        });
    }

    /**
     * Ordena pela vitrine do expositor, não por uma coluna de `products`.
     *
     * `sort_order` é decisão de quem vende — a mesma peça pode abrir a vitrine
     * de uma loja e fechar a de outra. A subconsulta evita o join que
     * duplicaria o produto quando houver mais de uma oferta.
     */
    public function scopeOrdenadoPelaVitrine(Builder $query): Builder
    {
        return $query->orderBy(
            ProductOffer::query()
                ->select('sort_order')
                ->whereColumn('product_offers.product_id', 'products.id')
                ->vigente()
                ->orderBy('sort_order')
                ->orderBy('id')
                ->limit(1)
        );
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->comOfertaVigente(apenasDestaques: true)->ordenadoPelaVitrine();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->comOfertaVigente()->ordenadoPelaVitrine();
    }

    public function scopeDoEixo(Builder $query, ItemType $type): Builder
    {
        return $query->where('item_type', $type->value)->comOfertaVigente();
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
