<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A FAQ que o expositor escreve sobre a própria oferta.
 *
 * Distinta de `ProductFaq`, que a partir da CAT-DOM-02D significa **FAQ
 * canônica** — afirmação do catálogo, sob curadoria (D-CAT-16). O mesmo texto
 * existindo nos dois lugares durante a transição é estado deliberado: a 02D
 * copia e não apaga a origem, e a limpeza pertence ao cutover da 02E.
 *
 * `sort_order` é **posição**, garantida única por oferta no schema. Quem
 * reescreve o conjunto substitui o conjunto inteiro em vez de calcular
 * diferença — atualizar posição a posição violaria a `UNIQUE` no meio, porque o
 * MySQL valida por *statement* e não no commit.
 */
class ProductOfferFaq extends Model
{
    protected $fillable = [
        'product_offer_id',
        'question',
        'answer',
        'sort_order',
    ];

    public function productOffer(): BelongsTo
    {
        return $this->belongsTo(ProductOffer::class);
    }
}
