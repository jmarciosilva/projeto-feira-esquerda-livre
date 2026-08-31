<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductQuestion extends Model
{
    protected $fillable = [
        'product_id',
        'product_offer_id',
        'user_id',
        'question',
        'answer',
        'answered_at',
        'answered_by',
        'is_visible',
    ];

    protected function casts(): array
    {
        return [
            'answered_at' => 'datetime',
            'is_visible' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * A oferta em que a pergunta foi feita (CAT-DOM-02D).
     *
     * Nula em linha legada e em pergunta cuja oferta foi removida — a FK é
     * `SET NULL`, porque a pergunta é conteúdo do cliente e sobrevive à saída
     * do expositor. `product_id` continua sendo o agrupamento canônico, e uma
     * coluna não substitui a outra (D-CAT-17).
     */
    public function productOffer(): BelongsTo
    {
        return $this->belongsTo(ProductOffer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function answeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'answered_by');
    }

    public function isAnswered(): bool
    {
        return $this->answered_at !== null;
    }

    /**
     * **Quem pode responder comercialmente esta pergunta** (CAT-DOM-02F,
     * D-02F-4).
     *
     * Só o expositor dono da oferta onde a pergunta foi feita. Até a 02E a
     * autorização perguntava outra coisa — *"tenho alguma oferta neste
     * produto?"* —, e a diferença era invisível porque `Product` e
     * `ProductOffer` andavam 1:1. Com dois vendedores no mesmo item, aquela
     * pergunta deixa B responder o que o cliente perguntou a A: o cliente
     * receberia, assinada pela loja errada, uma promessa de prazo ou de troca
     * que quem respondeu não tem como cumprir.
     *
     * **Pergunta sem oferta não tem destinatário comercial** (D-02F-5). A FK é
     * `nullable` por compatibilidade histórica, e nulo aqui significa
     * literalmente "não se sabe a quem foi feita". Deduzir o dono por
     * `product_id`, por `products.expositor_id`, pela primeira oferta ou por
     * `ofertaVigente` seria escolher um destinatário no lugar do cliente — e
     * atribuir a alguém uma pergunta que talvez não fosse para ele. Fica sem
     * resposta pelo painel do lojista até que um fluxo apropriado resolva o
     * contexto.
     *
     * Isto responde **quem é o destinatário comercial**, e não *"tem
     * permissão?"* no sentido amplo: curadoria e admin passam por
     * `Gate::before` e pelas permissões próprias, fora deste predicado.
     */
    public function podeSerRespondidaPor(?User $user): bool
    {
        return $this->productOffer?->pertenceAoExpositorDe($user) === true;
    }

    /**
     * As perguntas dirigidas às ofertas deste expositor.
     *
     * Contrapartida em consulta de `podeSerRespondidaPor()`: o filtro é a
     * oferta da pergunta, nunca "produtos em que eu tenho alguma oferta".
     * Perguntas sem `product_offer_id` ficam de fora — não são de ninguém.
     */
    public function scopeDirigidaAoExpositor(Builder $query, ?int $expositorId): Builder
    {
        if ($expositorId === null) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas(
            'productOffer',
            fn (Builder $offer) => $offer->where('expositor_id', $expositorId),
        );
    }

    /** Primeiro nome do perguntador, para exibição pública. */
    public function askerFirstName(): string
    {
        return explode(' ', $this->user->name ?? 'Visitante')[0];
    }
}
