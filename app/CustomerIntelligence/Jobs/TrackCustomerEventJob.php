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
 * Roda na fila propria do modulo (`customer-intelligence` por padrao), definida
 * no proprio construtor para que o destino esteja certo mesmo quando o job e
 * despachado diretamente, sem passar pelo service.
 *
 * Tudo o que depende da requisicao — sessao, usuario autenticado e o instante
 * do fato — e capturado no despacho e viaja com o job. Dentro do worker nao ha
 * cookie nem usuario logado para consultar.
 *
 * Estado na fase CI-04: o caminho funciona ponta a ponta e esta testado, mas
 * NENHUMA chamada da aplicacao o utiliza. Os sete eventos atuais continuam indo
 * para o SDK externo, sem escrita dupla. A ligacao e a CI-05.
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
        public readonly ?int $userId = null,
        ?DateTimeInterface $occurredAt = null,
    ) {
        // Congelado no despacho, nao no processamento: o atraso da fila nao
        // deve deslocar o instante em que o fato de negocio aconteceu.
        $this->occurredAt = $occurredAt ?? Carbon::now();

        $this->onConnection(config('customer-intelligence-internal.queue.connection'));
        $this->onQueue(config('customer-intelligence-internal.queue.name'));
    }

    public function handle(CustomerIntelligenceService $service): void
    {
        $service->record(
            event: $this->event,
            properties: $this->properties,
            entity: $this->entity,
            session: $this->session,
            userId: $this->userId,
            occurredAt: $this->occurredAt,
        );
    }
}
