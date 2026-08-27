<?php

namespace App\Models;

use App\Enums\Modality;
use App\Enums\PriceType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A oferta de um expositor sobre um item de catálogo.
 *
 * `Product` responde *o que é este item*; `ProductOffer` responde *quem vende,
 * por quanto e em que condições*. A distinção existe porque os dois têm ciclos
 * de vida diferentes: quando um expositor deixa a Feira, a oferta dele acaba e
 * o produto continua no catálogo, com todo o conhecimento acumulado, pronto
 * para quando outro expositor quiser oferecê-lo.
 *
 * A propriedade mora aqui. Um lojista é dono da **sua oferta**, nunca da
 * identidade global do produto — e `expositor_id` jamais é recalculado a partir
 * de quem está salvando (SEC-02).
 */
class ProductOffer extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'expositor_id',
        'price',
        'price_type',
        'modality',
        'duration_min',
        'weight',
        'height',
        'width',
        'length',
        'has_stock',
        'stock_quantity',
        'is_active',
        'is_featured',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price_type' => PriceType::class,
            'modality' => Modality::class,
            'price' => 'decimal:2',
            'weight' => 'decimal:3',
            'height' => 'decimal:2',
            'width' => 'decimal:2',
            'length' => 'decimal:2',
            'has_stock' => 'boolean',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function expositor(): BelongsTo
    {
        return $this->belongsTo(Expositor::class);
    }

    /**
     * Ofertas que o público pode ver e comprar.
     *
     * As três condições respondem a perguntas diferentes e nenhuma substitui a
     * outra: o lojista pode ter recolhido *este item* (`is_active` da oferta),
     * a loja inteira pode estar fora do ar (`expositor.is_active`) ou o item
     * pode ter saído do catálogo (`product.is_active`).
     *
     * Antes desta fase, o catálogo por eixo e os destaques da home olhavam só o
     * produto, enquanto a página da loja olhava o expositor — e um item de loja
     * inativa aparecia na listagem para dar 404 ao ser clicado. Concentrar a
     * regra aqui é o que impede essa divergência de voltar.
     */
    public function scopeVigente(Builder $query): Builder
    {
        return $query
            ->where('product_offers.is_active', true)
            ->whereHas('expositor', fn (Builder $q) => $q->where('is_active', true))
            ->whereHas('product', fn (Builder $q) => $q->where('is_active', true));
    }

    /**
     * Mesma regra do escopo, para uma oferta já carregada.
     *
     * Usada onde a decisão é sobre um registro só — página do item, adição ao
     * carrinho, cotação de frete — e montar uma consulta seria desperdício.
     */
    public function isVigente(): bool
    {
        return $this->is_active
            && (bool) $this->expositor?->is_active
            && (bool) $this->product?->is_active;
    }

    public function scopeDoExpositor(Builder $query, int $expositorId): Builder
    {
        return $query->where('expositor_id', $expositorId);
    }
}
