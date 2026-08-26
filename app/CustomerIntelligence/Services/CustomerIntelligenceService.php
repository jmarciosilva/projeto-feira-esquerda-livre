<?php

namespace App\CustomerIntelligence\Services;

use App\CustomerIntelligence\Actions\IncrementDailyMetric;
use App\CustomerIntelligence\Enums\EventName;
use App\CustomerIntelligence\Enums\MetricName;
use App\CustomerIntelligence\Jobs\TrackCustomerEventJob;
use App\CustomerIntelligence\Models\TrackedEvent;
use App\CustomerIntelligence\Models\Visitor;
use App\CustomerIntelligence\Models\VisitorSession;
use App\CustomerIntelligence\Support\PropertySanitizer;
use App\CustomerIntelligence\Support\VisitorContext;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Porta de entrada do modulo de Customer Intelligence.
 *
 * Grava direto no banco da propria Feira. Nao faz nenhuma chamada de rede.
 *
 * Dois caminhos de escrita:
 *
 *   track()   enfileira o evento e devolve o controle na hora — e o que a
 *             aplicacao usa, para nao pagar a gravacao na requisicao.
 *   record()  grava imediatamente. Usado pelo job e por quem precisa do
 *             registro de volta.
 *
 * A gravacao e atomica e idempotente:
 *
 *   atomica     o evento e todos os seus agregados diarios sao persistidos na
 *               mesma transacao. Nunca existe evento com metrica parcial.
 *   idempotente o `event_uuid` nasce no despacho e viaja com o job, entao uma
 *               retentativa reconhece o evento ja gravado em vez de duplica-lo.
 *
 * As duas propriedades se sustentam juntas: e por a agregacao estar dentro da
 * transacao que "o evento existe" pode ser lido como "as metricas dele tambem".
 */
class CustomerIntelligenceService
{
    public function __construct(
        private readonly PropertySanitizer $sanitizer,
        private readonly VisitorContext $context,
        private readonly IncrementDailyMetric $increment,
    ) {}

    /**
     * Enfileira a gravacao de um evento na fila propria do modulo.
     *
     * Sessao, usuario autenticado e instante do fato sao capturados agora e
     * viajam com o job: dentro do worker nao existe cookie nem usuario logado
     * para consultar.
     *
     * O `event_uuid` tambem nasce aqui, e nao no INSERT. E o que torna a
     * gravacao idempotente: uma retentativa do mesmo job carrega o mesmo
     * identificador e a chave unica de `ci_events` reconhece o evento como ja
     * gravado. Se o UUID fosse gerado no Model, cada retentativa produziria um
     * identificador diferente e um evento duplicado.
     *
     * @param  array<string, mixed>  $properties
     */
    public function track(
        EventName $event,
        array $properties = [],
        ?Model $entity = null,
        ?VisitorSession $session = null,
    ): void {
        $session ??= $this->context->session();

        TrackCustomerEventJob::dispatch(
            $event,
            $properties,
            $entity,
            $session,
            $session?->visitor?->user_id ?? Auth::id(),
            Carbon::now(),
            (string) Str::orderedUuid(),
        );
    }

    /**
     * Grava um evento comportamental imediatamente.
     *
     * @param  array<string, mixed>  $properties
     */
    public function record(
        EventName $event,
        array $properties = [],
        ?Model $entity = null,
        ?VisitorSession $session = null,
        ?Visitor $visitor = null,
        ?int $userId = null,
        ?DateTimeInterface $occurredAt = null,
        ?string $eventUuid = null,
    ): TrackedEvent {
        // Sem visitante/sessao explicitos, usa o que o middleware resolveu para
        // esta requisicao. Fora do ciclo HTTP o contexto esta vazio e o evento e
        // gravado sem vinculo, em vez de descartado.
        $session ??= $this->context->session();
        $visitor ??= $session?->visitor;

        $atributos = array_filter([
            'event_uuid' => $eventUuid,
            'visitor_id' => $visitor?->getKey(),
            'session_id' => $session?->getKey(),
            'user_id' => $this->resolveUserId($userId, $visitor),
            'event_name' => $event,
            'event_category' => $event->category(),
            'entity_type' => $entity === null ? null : $entity->getMorphClass(),
            'entity_id' => $entity?->getKey(),
            'properties' => $properties === [] ? null : $this->sanitizer->sanitize($properties),
            'occurred_at' => $occurredAt ?? now(),
        ], fn ($valor) => $valor !== null);

        try {
            // Evento e agregados numa unidade so. Sem isto, uma falha no meio
            // da agregacao deixaria o evento gravado com metricas parciais — e
            // a retentativa, reconhecendo o evento como ja existente, nunca
            // completaria a soma. O estado valido e sempre "nada gravado" ou
            // "evento mais todas as suas metricas".
            return DB::transaction(function () use ($atributos) {
                $tracked = TrackedEvent::create($atributos);

                $this->aggregate($tracked);

                return $tracked;
            });
        } catch (UniqueConstraintViolationException $excecao) {
            // Fora da transacao de proposito: quando a colisao acontece, o
            // rollback ja ocorreu e a consulta abaixo enxerga o estado
            // definitivo do banco.
            return $this->alreadyRecorded($eventUuid, $excecao);
        }
    }

    /**
     * Trata a colisao da chave unica de `ci_events`.
     *
     * Se existir um evento com o mesmo `event_uuid`, a colisao e a retentativa
     * de um job que ja tinha gravado — devolvemos o registro existente e
     * seguimos em frente. Qualquer outra violacao de unicidade e falha real e
     * volta a subir: idempotencia nao pode virar desculpa para engolir erro.
     */
    private function alreadyRecorded(?string $eventUuid, UniqueConstraintViolationException $excecao): TrackedEvent
    {
        $existente = $eventUuid === null
            ? null
            : TrackedEvent::where('event_uuid', $eventUuid)->first();

        if ($existente === null) {
            throw $excecao;
        }

        return $existente;
    }

    /**
     * Atualiza os agregados diarios que o painel consulta.
     *
     * Incremental: cada evento soma 1 no dia em que ocorreu, em vez de o painel
     * recontar `ci_events` inteira a cada carregamento. Roda dentro do job de
     * gravacao, entao fica fora do caminho da requisicao.
     *
     * Chamado sempre dentro da transacao de `record()`: se qualquer incremento
     * falhar, o evento tambem nao fica.
     */
    private function aggregate(TrackedEvent $tracked): void
    {
        $dia = $tracked->occurred_at;

        ($this->increment)(MetricName::Eventos, $dia);

        ($this->increment)(
            MetricName::Eventos,
            $dia,
            dimensionType: MetricName::DIMENSION_EVENT_NAME,
            dimensionValue: $tracked->event_name->value,
        );

        if ($tracked->event_name === MetricName::conversionEvent()) {
            ($this->increment)(MetricName::Conversoes, $dia);
        }
    }

    /**
     * Precedencia: o usuario capturado no despacho vence, porque foi apurado
     * quando a requisicao ainda existia — o job pode rodar depois do logout.
     * Sem ele, vale o vinculo registrado no visitante e, por ultimo, a sessao
     * HTTP atual (que dentro de um worker simplesmente nao existe).
     */
    private function resolveUserId(?int $userId, ?Visitor $visitor): ?int
    {
        return $userId ?? $visitor?->user_id ?? Auth::id();
    }
}
