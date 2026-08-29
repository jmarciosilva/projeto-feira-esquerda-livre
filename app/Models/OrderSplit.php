<?php

namespace App\Models;

use App\Enums\OrderSplitStatus;
use App\Events\OrderSplitConfirmed;
use App\Events\OrderSplitReverted;
use App\Exceptions\SplitRevertidoNaoReconfirma;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Relations\HasOne;

class OrderSplit extends Model
{
    protected $fillable = [
        'order_id',
        'expositor_id',
        'expositor_name',
        'gross_amount',
        'commission_percent',
        'commission_amount',
        'net_amount',
        'shipping_amount',
        'status',
        'confirmed_at',
        'reverted_at',
    ];

    protected function casts(): array
    {
        return [
            'status'              => OrderSplitStatus::class,
            'gross_amount'        => 'decimal:2',
            'commission_percent'  => 'decimal:2',
            'commission_amount'   => 'decimal:2',
            'net_amount'          => 'decimal:2',
            'shipping_amount'     => 'decimal:2',
            'confirmed_at'        => 'datetime',
            'reverted_at'         => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function expositor(): BelongsTo
    {
        return $this->belongsTo(Expositor::class);
    }

    public function shipping(): HasOne
    {
        return $this->hasOne(OrderShipping::class, 'order_split_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(OrderMessage::class, 'order_split_id')->orderBy('created_at');
    }

    /**
     * Transicao do split para confirmado, com o evento que carrega os efeitos.
     *
     * Split ja confirmado nao transiciona de novo: sem essa guarda, dois
     * cliques do lojista — ou dois requests da API — disparariam o evento duas
     * vezes, e cada disparo carrega efeito de negocio.
     *
     * O evento sai **depois do commit**: os listeners matriculam aluno e mandam
     * e-mail, e nada disso pode acontecer sobre um estado que ainda pode sofrer
     * rollback. Fora de transacao — a confirmacao manual do lojista —
     * `afterCommit` executa na hora, entao aquele fluxo nao muda.
     */
    public function confirmar(): void
    {
        if ($this->status === OrderSplitStatus::Confirmado) {
            return;
        }

        // Revertido e terminal. Sem esta guarda, o botao do lojista ou a rota
        // `PATCH /pedidos/{split}/confirmar-pagamento` reconfirmariam um split
        // cujo pagamento foi desfeito — e cada confirmacao redispara
        // `OrderSplitConfirmed`, que matricula aluno e emite evento.
        if ($this->status === OrderSplitStatus::Revertido) {
            throw new SplitRevertidoNaoReconfirma($this);
        }

        $this->update([
            'status'       => OrderSplitStatus::Confirmado,
            'confirmed_at' => now(),
        ]);

        DB::afterCommit(fn () => event(new OrderSplitConfirmed($this)));
    }

    /**
     * Transicao do split para revertido: o pagamento que sustentava este repasse
     * foi desfeito.
     *
     * ## O que esta transicao **nao** apaga
     *
     * `confirmed_at` continua onde estava. Ele responde "quando este repasse
     * passou a ser devido?", e a resposta segue verdadeira depois da reversao —
     * exatamente como `paid_at` no pedido. Quando deixou de ser devido e outra
     * pergunta, e por isso e outra coluna.
     *
     * ## Idempotencia
     *
     * Refund reentregue pelo gateway chega aqui duas vezes. A segunda encontra
     * o split ja `Revertido` e nao faz nada: nao remarca `reverted_at`, e
     * sobretudo nao redispara `OrderSplitReverted`, cujo listener revoga acesso.
     *
     * ## Pendente tambem reverte
     *
     * Um split que nunca chegou a confirmar — porque o pagamento falhou no meio,
     * ou porque a reversao chegou antes da confirmacao terminar de propagar —
     * nao pode ficar `Pendente` num pedido estornado, aparecendo ao lojista como
     * repasse a caminho. Ele vai para `Revertido` sem disparar efeito nenhum,
     * porque nao ha acesso concedido para revogar.
     */
    public function reverter(): void
    {
        if ($this->status === OrderSplitStatus::Revertido) {
            return;
        }

        $eraConfirmado = $this->status === OrderSplitStatus::Confirmado;

        $this->update([
            'status' => OrderSplitStatus::Revertido,
            'reverted_at' => now(),
        ]);

        if ($eraConfirmado) {
            DB::afterCommit(fn () => event(new OrderSplitReverted($this)));
        }
    }
}
