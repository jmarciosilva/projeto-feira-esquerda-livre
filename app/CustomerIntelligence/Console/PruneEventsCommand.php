<?php

namespace App\CustomerIntelligence\Console;

use App\CustomerIntelligence\Models\TrackedEvent;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Expurga eventos brutos fora da janela de retencao.
 *
 * A politica e 180 dias para `ci_events` e retencao permanente para
 * `ci_daily_metrics`. E o agregado que torna o expurgo viavel: o painel le dele,
 * entao apagar o evento bruto nao apaga a serie historica.
 *
 * Fronteira: removemos `occurred_at < cutoff`, onde cutoff e o instante de
 * agora menos os dias de retencao. Um evento com exatamente 180 dias FICA;
 * apenas o estritamente mais velho sai.
 *
 * A idade vem de `occurred_at`, e nao de `created_at`, porque o fato pode ter
 * sido gravado bem depois de acontecer — o job congela o instante do fato no
 * despacho e a fila pode demorar.
 */
class PruneEventsCommand extends Command
{
    protected $signature = 'customer-intelligence:prune-events
                            {--days= : Dias de retenção. Padrão: o configurado no módulo}
                            {--chunk=1000 : Tamanho do lote de exclusão}
                            {--dry-run : Apenas relata o que seria removido}';

    protected $description = 'Remove eventos de Customer Intelligence fora da janela de retenção';

    public function handle(): int
    {
        // `?:` cairia no padrao para --days=0, porque '0' e falsy em PHP —
        // o usuario pediria "apagar tudo" e receberia 180 dias em silencio.
        $informado = $this->option('days');
        $dias = $informado === null
            ? (int) config('customer-intelligence-internal.retention.event_days', 180)
            : (int) $informado;

        $lote = max(1, (int) $this->option('chunk'));
        $simulacao = (bool) $this->option('dry-run');

        if ($dias < 1) {
            $this->components->error(
                'A retenção precisa ser de pelo menos 1 dia. Nada foi removido.'
            );

            return self::FAILURE;
        }

        $cutoff = Carbon::now()->subDays($dias);

        $alvo = TrackedEvent::where('occurred_at', '<', $cutoff);
        $total = $alvo->count();

        $this->components->info(sprintf(
            'Retenção de %d dias. Corte em %s. Eventos fora da janela: %d.',
            $dias,
            $cutoff->toDateTimeString(),
            $total
        ));

        if ($total === 0) {
            return self::SUCCESS;
        }

        if ($simulacao) {
            $this->components->warn('Dry-run: nada foi removido.');

            return self::SUCCESS;
        }

        $removidos = $this->pruneInBatches($cutoff, $lote);

        $this->components->info($removidos.' eventos removidos. Os agregados diários foram preservados.');

        return self::SUCCESS;
    }

    /**
     * Remove em lotes, sem nunca materializar os modelos.
     *
     * Selecionamos apenas as chaves e apagamos por `whereIn`: `DELETE ... LIMIT`
     * nao e portavel (o SQLite so o suporta com uma flag de compilacao), e
     * carregar os registros com `get()` estouraria a memoria num historico
     * grande.
     */
    private function pruneInBatches(Carbon $cutoff, int $lote): int
    {
        $removidos = 0;

        do {
            $ids = TrackedEvent::where('occurred_at', '<', $cutoff)
                ->orderBy('id')
                ->limit($lote)
                ->pluck('id');

            if ($ids->isEmpty()) {
                break;
            }

            $removidos += TrackedEvent::whereIn('id', $ids)->delete();
        } while ($ids->count() === $lote);

        return $removidos;
    }
}
