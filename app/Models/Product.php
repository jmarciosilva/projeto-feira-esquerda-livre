<?php

namespace App\Models;

use App\Enums\ItemType;
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

    /**
     * Só o que o item **é**.
     *
     * Os doze espelhos comerciais saíram na CAT-DOM-02H, junto com as colunas.
     * `is_active` fica: no produto é validade canônica do item no catálogo
     * (D-CAT-10), e nunca foi espelho de nada. `expositor_id` fica: é
     * proveniência (D-CAT-11), não propriedade.
     */
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
        'is_active',
        'is_digital',
    ];

    protected function casts(): array
    {
        return [
            'item_type' => ItemType::class,
            'is_active' => 'boolean',
            'is_digital' => 'boolean',
            'images' => 'array',
            'canonical_delegated_at' => 'datetime',
            'canonical_delegation_revoked_at' => 'datetime',
        ];
    }

    /**
     * Os campos que respondem *o que este item é*, e que só mudam com
     * autoridade canônica — curadoria ou delegação válida (D-CAT-09).
     *
     * `slug` fica fora porque é derivado do nome pela plataforma, não escolhido;
     * `is_active` fica fora porque é validade canônica e pertence exclusivamente
     * à curadoria (D-CAT-10); `images` fica fora porque seu desdobramento em
     * imagem canônica e imagem da oferta é a CAT-DOM-02D (D-CAT-14).
     */
    public const CAMPOS_CANONICOS = [
        'name',
        'short_description',
        'description',
        'item_type',
        'category_id',
        'is_digital',
    ];

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

    /**
     * O expositor a quem a plataforma delegou a edição canônica deste item.
     *
     * Não confundir com `expositor()`: aquela é proveniência — quem trouxe o
     * item ao catálogo —, esta é autoridade. Hoje as duas apontam para o mesmo
     * expositor em toda a base, porque o backfill da CAT-DOM-02C inicializou
     * uma a partir da outra; elas divergem no primeiro ato de curadoria, e
     * **nenhuma autorização pode ler a primeira** (D-CAT-11).
     */
    public function canonicalDelegate(): BelongsTo
    {
        return $this->belongsTo(Expositor::class, 'canonical_delegate_expositor_id');
    }

    /**
     * Existe delegação canônica em vigor sobre este item?
     *
     * Repare no que **não** aparece aqui: nenhuma contagem de ofertas, nenhuma
     * leitura de `expositor_id`. A delegação é um fato declarado, e some por
     * revogação ou pela saída do expositor (`nullOnDelete`) — nunca porque o
     * número de ofertas mudou (D-CAT-09, §4.3.1).
     */
    public function temDelegacaoCanonicaAtiva(): bool
    {
        return $this->canonical_delegate_expositor_id !== null
            && $this->canonical_delegation_revoked_at === null;
    }

    /** A delegação em vigor pertence a este expositor? */
    public function delegaCanonicoPara(?int $expositorId): bool
    {
        return $expositorId !== null
            && $this->temDelegacaoCanonicaAtiva()
            && (int) $this->canonical_delegate_expositor_id === $expositorId;
    }

    /**
     * Concede a delegação canônica a um expositor.
     *
     * `forceFill` porque as colunas de governança ficam **fora do `$fillable`**
     * de propósito: elas não podem entrar por formulário nem por payload de
     * API. Quem concede é o domínio, nunca um `update($request->all())`.
     */
    public function delegarCanonicoPara(int $expositorId): void
    {
        $this->forceFill([
            'canonical_delegate_expositor_id' => $expositorId,
            'canonical_delegated_at' => now(),
            'canonical_delegation_revoked_at' => null,
        ])->save();
    }

    /**
     * Revoga a delegação, preservando quem a detinha e desde quando.
     *
     * A linha não é apagada: `canonical_delegate_expositor_id` e
     * `canonical_delegated_at` continuam contando o que houve, e
     * `canonical_delegation_revoked_at` diz que acabou. Sem isso, revogar
     * apagaria a própria evidência de que alguém já teve a delegação.
     */
    public function revogarDelegacaoCanonica(): void
    {
        if (! $this->temDelegacaoCanonicaAtiva()) {
            return;
        }

        $this->forceFill(['canonical_delegation_revoked_at' => now()])->save();
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
