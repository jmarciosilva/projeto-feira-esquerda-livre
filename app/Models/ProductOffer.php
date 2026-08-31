<?php

namespace App\Models;

use App\Enums\Modality;
use App\Enums\PriceType;
use App\Exceptions\OfertaComReservaAtiva;
use App\Support\PublicUrl;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'images',
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
        'reserved_quantity',
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
            'images' => 'array',
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
     * A FAQ comercial desta oferta (CAT-DOM-02D).
     *
     * Não confundir com `Product::faqs()`, que a partir desta fase significa
     * FAQ canônica. Nada em runtime lê esta relação ainda — o formulário e a
     * API continuam escrevendo em `product_faqs` até a 02E. Ela existe porque o
     * backfill e a verificação de integridade precisam dela.
     */
    public function offerFaqs(): HasMany
    {
        return $this->hasMany(ProductOfferFaq::class)->orderBy('sort_order');
    }

    /** As perguntas feitas nesta oferta (CAT-DOM-02D). */
    public function questions(): HasMany
    {
        return $this->hasMany(ProductQuestion::class);
    }

    /**
     * As imagens a exibir no contexto comercial desta oferta (CAT-DOM-02E).
     *
     * A cadeia é **decisão de leitura**, nunca persistência:
     *
     * ```text
     * ProductOffer.images   imagem que o lojista enviou
     * → Product.images      imagem canônica do item
     * → Product.image_path  espelho legado do primeiro medium (dívida D-1)
     * → []                  quem chama decide o placeholder
     * ```
     *
     * O fallback existe porque os 75 itens desta base nasceram antes da 02D e
     * porque a curadoria pode vir a dar imagem canônica a um item que nenhum
     * lojista ilustrou. O que ele **não** faz é copiar path para dentro de
     * `ProductOffer.images`: fallback de leitura que grava vira compartilhamento
     * de arquivo físico, e é exatamente o que o §17 da 02D proíbe — com
     * `ImageService::delete()` apagando por caminho e sem contar referências, o
     * lojista removendo a imagem dele levaria junto a do catálogo.
     *
     * @return list<array<string, string>>
     */
    public function imagensParaExibicao(): array
    {
        $daOferta = $this->images ?? [];

        if ($daOferta !== []) {
            return $daOferta;
        }

        $canonicas = $this->product?->images ?? [];

        if ($canonicas !== []) {
            return $canonicas;
        }

        $legado = $this->product?->image_path;

        return $legado ? [['thumb' => $legado, 'medium' => $legado]] : [];
    }

    /**
     * URL da imagem principal para exibição comercial.
     *
     * `$tamanho` aceita `medium` (detalhe) ou `thumb` (listagem); a outra chave
     * serve de reserva quando a entrada só tem uma das duas.
     */
    public function urlDaImagemPrincipal(string $tamanho = 'medium'): ?string
    {
        $primeira = $this->imagensParaExibicao()[0] ?? null;

        if ($primeira === null) {
            return null;
        }

        $reserva = $tamanho === 'thumb' ? 'medium' : 'thumb';
        $path = $primeira[$tamanho] ?? $primeira[$reserva] ?? null;

        return $path ? PublicUrl::for($path) : null;
    }

    protected static function booted(): void
    {
        // Última linha de defesa, e não o controle de concorrência: quem
        // precisa apagar uma oferta deve passar por `DeleteProductOffer`, que
        // faz a leitura sob lock. Este guarda existe para que nenhum caminho
        // futuro — comando, painel novo, tinker — consiga apagar uma oferta que
        // ainda deve unidades a um pedido só porque esqueceu da regra.
        static::deleting(function (ProductOffer $offer) {
            $reservado = (int) static::query()
                ->whereKey($offer->getKey())
                ->value('reserved_quantity');

            if ($reservado > 0) {
                throw new OfertaComReservaAtiva(
                    $offer->product?->name ?? 'Esta oferta',
                    $reservado,
                );
            }
        });
    }

    /**
     * Quantas unidades ainda podem ser vendidas.
     *
     * `null` quando a oferta não controla estoque — as duas formas de dizer
     * ilimitado que o cadastro sempre teve: `has_stock` falso ou quantidade em
     * branco.
     */
    public function disponivel(): ?int
    {
        if (! $this->has_stock || $this->stock_quantity === null) {
            return null;
        }

        return max(0, (int) $this->stock_quantity - (int) $this->reserved_quantity);
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

    /**
     * **Esta oferta é sua?** — a única definição de ownership comercial
     * (CAT-DOM-02F, D-02F-1).
     *
     * A resposta sai de `product_offers.expositor_id`, e de mais nada. Os três
     * atalhos que parecem equivalentes e não são:
     *
     * - **`products.expositor_id`** é proveniência (D-CAT-11): registra quem
     *   trouxe o item ao catálogo, um fato histórico que não acompanha quem
     *   vende hoje. O item pode ter sido cadastrado por A e ser oferecido só
     *   por B;
     * - **`canonical_delegate_expositor_id`** é poder sobre *o que o item é*,
     *   concedido e revogável (D-CAT-09). Editar a identidade do produto e ser
     *   dono da oferta são eixos independentes;
     * - **cardinalidade** — "o produto só tem uma oferta, logo é dele" — é
     *   estado comercial passageiro. Código que autoriza assim fica correto
     *   hoje e errado no dia em que o segundo expositor aparecer.
     *
     * ## Por que um predicado, e não uma Policy
     *
     * `Gate::before` concede tudo a admin antes de qualquer Policy rodar, e
     * admin **não tem expositor**. Uma Policy responderia "pode" e o código
     * seguinte quebraria no expositor nulo — foi essa a razão registrada na
     * SEC-02, e ela não mudou. A autoridade **canônica** continua na
     * `ProductPolicy`, onde o override de admin é desejado; o ownership
     * **comercial** mora aqui, onde ele não é.
     */
    public function pertenceAoExpositorDe(?User $user): bool
    {
        $expositorId = $user?->expositor?->id;

        return $expositorId !== null && (int) $this->expositor_id === (int) $expositorId;
    }
}
