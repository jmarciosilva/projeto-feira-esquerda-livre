<?php

namespace Tests\Feature\CustomerIntelligence;

use App\CustomerIntelligence\Actions\IncrementDailyMetric;
use App\CustomerIntelligence\Enums\EventName;
use App\CustomerIntelligence\Enums\MetricName;
use App\CustomerIntelligence\Models\DailyMetric;
use App\CustomerIntelligence\Models\TrackedEvent;
use App\CustomerIntelligence\Services\CustomerIntelligenceService;
use App\CustomerIntelligence\Support\PropertySanitizer;
use App\CustomerIntelligence\Support\VisitorContext;
use DateTimeInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

/**
 * Atomicidade entre o evento e seus agregados.
 *
 * O estado valido e sempre um dos dois:
 *
 *   nada gravado
 *   evento + TODAS as suas metricas
 *
 * Nunca evento sem metrica, nem metrica parcial. Sem isso a idempotencia se
 * volta contra o sistema: a retentativa reconheceria o evento como ja gravado e
 * nunca completaria a soma que faltou.
 */
class InternalAtomicityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Incrementador que funciona nas primeiras N chamadas e falha na seguinte.
     * Com N = 1 reproduz a falha PARCIAL: uma metrica ja foi somada quando a
     * proxima explode.
     */
    private function incrementadorQueFalhaApos(int $sucessos): IncrementDailyMetric
    {
        return new class($sucessos) extends IncrementDailyMetric
        {
            public int $chamadas = 0;

            public function __construct(private readonly int $sucessos) {}

            public function __invoke(
                MetricName $metric,
                DateTimeInterface|string|null $date = null,
                float $delta = 1,
                string $dimensionType = '',
                string $dimensionValue = '',
            ): void {
                $this->chamadas++;

                if ($this->chamadas > $this->sucessos) {
                    throw new RuntimeException('falha simulada na agregação');
                }

                parent::__invoke($metric, $date, $delta, $dimensionType, $dimensionValue);
            }
        };
    }

    private function servicoCom(IncrementDailyMetric $incrementador): CustomerIntelligenceService
    {
        return new CustomerIntelligenceService(
            new PropertySanitizer,
            app(VisitorContext::class),
            $incrementador,
        );
    }

    private function servicoNormal(): CustomerIntelligenceService
    {
        return app(CustomerIntelligenceService::class);
    }

    // ─── Rollback ─────────────────────────────────────────────────────────

    public function test_a_failure_in_the_very_first_metric_rolls_back_the_event(): void
    {
        $servico = $this->servicoCom($this->incrementadorQueFalhaApos(0));

        try {
            $servico->record(EventName::PedidoCriado, eventUuid: (string) Str::orderedUuid());
            $this->fail('A falha na agregação deveria ter subido.');
        } catch (RuntimeException) {
            // esperado
        }

        $this->assertSame(0, TrackedEvent::count(), 'O evento não pode ficar gravado.');
        $this->assertSame(0, DailyMetric::count(), 'Nenhuma métrica pode ficar gravada.');
    }

    /**
     * O caso que motivou a correção: a primeira métrica JÁ foi somada quando a
     * segunda falha. Sem transação, o evento e o primeiro incremento ficariam
     * gravados para sempre, e a retentativa nunca completaria o resto.
     */
    public function test_a_partial_aggregation_leaves_nothing_behind(): void
    {
        $incrementador = $this->incrementadorQueFalhaApos(1);
        $servico = $this->servicoCom($incrementador);

        try {
            $servico->record(EventName::PedidoCriado, eventUuid: (string) Str::orderedUuid());
            $this->fail('A falha na agregação deveria ter subido.');
        } catch (RuntimeException) {
            // esperado
        }

        $this->assertSame(2, $incrementador->chamadas, 'A primeira métrica chegou a ser somada.');
        $this->assertSame(0, TrackedEvent::count(), 'Mesmo assim o evento sofreu rollback.');
        $this->assertSame(0, DailyMetric::count(), 'E a métrica parcial também.');
    }

    public function test_a_failure_in_the_conversion_metric_also_rolls_back(): void
    {
        // pedido.criado gera 3 incrementos: total, por nome e conversão.
        $incrementador = $this->incrementadorQueFalhaApos(2);
        $servico = $this->servicoCom($incrementador);

        try {
            $servico->record(EventName::PedidoCriado, eventUuid: (string) Str::orderedUuid());
            $this->fail('A falha na agregação deveria ter subido.');
        } catch (RuntimeException) {
            // esperado
        }

        $this->assertSame(3, $incrementador->chamadas);
        $this->assertSame(0, TrackedEvent::count());
        $this->assertSame(0, DailyMetric::count());
    }

    // ─── Retentativa depois do rollback ───────────────────────────────────

    public function test_a_retry_after_a_rollback_records_exactly_once(): void
    {
        $uuid = (string) Str::orderedUuid();

        // Primeira tentativa: falha no meio da agregação.
        try {
            $this->servicoCom($this->incrementadorQueFalhaApos(1))
                ->record(EventName::PedidoCriado, eventUuid: $uuid);
        } catch (RuntimeException) {
            // esperado
        }

        $this->assertSame(0, TrackedEvent::count());

        // Retentativa do mesmo evento lógico, agora sem falha.
        $this->servicoNormal()->record(EventName::PedidoCriado, eventUuid: $uuid);

        $this->assertSame(1, TrackedEvent::count());
        $this->assertSame($uuid, TrackedEvent::sole()->event_uuid);

        $hoje = TrackedEvent::sole()->occurred_at->toDateString();
        $this->assertSame(
            '1.0000',
            DailyMetric::where('metric_name', MetricName::Eventos->value)
                ->where('dimension_type', '')->where('metric_date', $hoje)->value('metric_value'),
            'Total de eventos somado uma única vez.'
        );
        $this->assertSame(
            '1.0000',
            DailyMetric::where('metric_name', MetricName::Eventos->value)
                ->where('dimension_type', MetricName::DIMENSION_EVENT_NAME)
                ->where('dimension_value', 'pedido.criado')->value('metric_value'),
            'Dimensão somada uma única vez.'
        );
        $this->assertSame(
            '1.0000',
            DailyMetric::where('metric_name', MetricName::Conversoes->value)->value('metric_value'),
            'Conversão somada uma única vez.'
        );
    }

    public function test_the_successful_path_still_writes_the_complete_set(): void
    {
        $this->servicoNormal()->record(EventName::PedidoCriado, eventUuid: (string) Str::orderedUuid());

        $this->assertSame(1, TrackedEvent::count());
        $this->assertSame(3, DailyMetric::count(), 'Total, dimensão e conversão.');
    }

    /**
     * Deadlock, timeout e conexao perdida nao sao sinal de evento ja
     * persistido: precisam dar rollback e continuar subindo para o retry
     * normal da fila. So a colisao de `event_uuid` indica retentativa.
     */
    public function test_a_generic_failure_is_not_mistaken_for_idempotency(): void
    {
        $this->expectException(RuntimeException::class);

        $this->servicoCom($this->incrementadorQueFalhaApos(0))
            ->record(EventName::ProdutoVisualizado, eventUuid: (string) Str::orderedUuid());
    }
}
