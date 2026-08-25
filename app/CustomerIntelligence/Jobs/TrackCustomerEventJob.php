<?php

namespace App\CustomerIntelligence\Jobs;

use App\CustomerIntelligence\Enums\EventName;
use App\CustomerIntelligence\Models\VisitorSession;
use App\CustomerIntelligence\Services\CustomerIntelligenceService;
use DateTimeInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

/**
 * Grava um evento comportamental fora do ciclo da requisicao.
 *
 * Estado na fase CI-02: o job existe, funciona e esta testado, mas NADA na
 * aplicacao o despacha. Os sete eventos atuais continuam indo para o SDK
 * externo, sem escrita dupla. A ligacao das chamadas reais e a CI-06.
 *
 * Fica na fila padrao de proposito: a CI-02 nao altera o servico `queue` do
 * Docker. A decisao sobre uma fila dedicada pertence a CI-05, junto com o
 * ajuste do parametro `--queue` do worker.
 */
class TrackCustomerEventJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public readonly DateTimeInterface $occurredAt;

    /**
     * @param  array<string, mixed>  $properties
     */
    public function __construct(
        public readonly EventName $event,
        public readonly array $properties = [],
        public readonly ?Model $entity = null,
        public readonly ?VisitorSession $session = null,
        ?DateTimeInterface $occurredAt = null,
    ) {
        // Congelado no despacho, nao no processamento: o atraso da fila nao
        // deve deslocar o instante em que o fato de negocio aconteceu.
        $this->occurredAt = $occurredAt ?? Carbon::now();
    }

    public function handle(CustomerIntelligenceService $service): void
    {
        $service->record(
            event: $this->event,
            properties: $this->properties,
            entity: $this->entity,
            session: $this->session,
            occurredAt: $this->occurredAt,
        );
    }
}
