<?php

namespace App\Models\Ava;

use App\Enums\AvaEnrollmentStatus;
use App\Models\OrderItem;
use App\Models\OrderSplit;
use App\Models\ProductOffer;
use App\Models\User;
use App\Services\AvaCertificateService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AvaEnrollment extends Model
{
    protected $fillable = [
        'user_id',
        'course_id',
        'order_split_id',
        'status',
        'enrolled_at',
        'expires_at',
        'completed_at',
        'completion_percent',
        'certificate_path',
        'last_accessed_at',
    ];

    protected $casts = [
        'status'             => AvaEnrollmentStatus::class,
        'enrolled_at'        => 'datetime',
        'expires_at'         => 'datetime',
        'completed_at'       => 'datetime',
        'last_accessed_at'   => 'datetime',
        'completion_percent' => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(AvaCourse::class, 'course_id');
    }

    public function orderSplit(): BelongsTo
    {
        return $this->belongsTo(OrderSplit::class, 'order_split_id');
    }

    public function progress(): HasMany
    {
        return $this->hasMany(AvaLessonProgress::class, 'enrollment_id');
    }

    /**
     * **A oferta que originou esta matrícula** (CAT-DOM-02G, D-02G-5).
     *
     * O curso pertence ao `Product` — `ava_courses.product_id` é UNIQUE, e o
     * conteúdo educacional é canônico, o mesmo para quem quer que venda o item.
     * O que é comercial é a **compra**, e é ela que esta matrícula referencia
     * por `order_split_id`.
     *
     * O caminho é inteiramente histórico e já existia no schema desde a
     * FIN-SEC-01B; o que faltava era alguém percorrê-lo:
     *
     * ```text
     * matrícula → order_split → order → order_items
     *           → item cujo product_id é o do curso
     *           → product_offer_id  (a oferta efetivamente comprada)
     * ```
     *
     * **Nunca `ofertaVigente`.** Resolver pelo estado atual faria o aluno que
     * comprou de B ver a loja de A assim que B recolhesse a oferta — reescrever
     * de quem ele comprou porque o catálogo mudou depois. É a mesma razão pela
     * qual `order_items` guarda `expositor_name` e `unit_price` em vez de reler
     * o produto.
     *
     * Retorna `null` quando não há compra por trás — matrícula de cortesia dada
     * pelo admin, por exemplo (`order_split_id` nulo). Nulo aqui significa
     * "não houve oferta de origem", e não "descubra qual foi".
     */
    public function ofertaDeOrigem(): ?ProductOffer
    {
        $split = $this->orderSplit;
        $productId = $this->course?->product_id;

        if ($split === null || $productId === null) {
            return null;
        }

        // O item é buscado pelo produto **e** pelo expositor do split: um
        // pedido pode reunir itens de várias lojas, e o split é de uma só.
        $item = OrderItem::query()
            ->where('order_id', $split->order_id)
            ->where('product_id', $productId)
            ->where('expositor_id', $split->expositor_id)
            ->first();

        return $item?->offer;
    }

    /**
     * O expositor que vendeu esta matrícula.
     *
     * Vem do `order_split`, que guarda `expositor_id` **e** `expositor_name` em
     * snapshot: a resposta sobrevive à loja sair da Feira, que é justamente
     * quando ela mais importa.
     */
    public function expositorDeOrigemId(): ?int
    {
        return $this->orderSplit?->expositor_id;
    }

    public function isAccessible(): bool
    {
        if (! $this->status->isAccessible()) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }

    public function updateCompletionPercent(): void
    {
        $totalLessons = $this->course->totalLessons();

        if ($totalLessons === 0) {
            return;
        }

        $wasCompleted = $this->isCompleted();

        $completedCount = $this->progress()->whereNotNull('completed_at')->count();
        $percent = round(($completedCount / $totalLessons) * 100, 2);

        $this->update([
            'completion_percent' => $percent,
            'completed_at'       => $percent >= 100 ? now() : null,
            'last_accessed_at'   => now(),
        ]);

        // Gera certificado na primeira vez que atinge 100%
        if (! $wasCompleted && $percent >= 100) {
            try {
                app(AvaCertificateService::class)->generate($this);
            } catch (\Throwable) {
                // Não impede o progresso se a geração falhar
            }
        }
    }
}
