<?php

namespace App\CustomerIntelligence\Console;

use App\CustomerIntelligence\Models\AuditLog;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Expurga registros de auditoria fora da janela de retencao.
 *
 * Comando PROPRIO, e nao uma opcao do `prune-events`. Os dois expurgam coisas
 * de naturezas diferentes, com prazos diferentes — 730 dias aqui contra 180 la
 * — e acopla-los faria uma mudanca de politica de analytics arrastar a trilha
 * de auditoria junto, silenciosamente. Sao rotinas separadas, com agendamentos
 * separados, de proposito.
 *
 * A idade vem de `created_at`, que aqui e o proprio instante do fato: uma linha
 * de auditoria e gravada de forma sincrona, no momento do acesso, sem fila no
 * meio. Nao existe o descompasso que em `ci_events` obriga a usar `occurred_at`.
 *
 * Fronteira igual a do expurgo de eventos: removemos `created_at < cutoff`. Um
 * registro com exatamente 730 dias FICA.
 *
 * Este comando nao se audita. Uma rotina de retencao que gerasse registro de
 * auditoria a cada execucao criaria exatamente o rastro que ela existe para
 * limitar — e, no limite, uma trilha que nunca esvazia.
 */
class PruneAuditLogsCommand extends Command
{
    protected $signature = 'customer-intelligence:prune-audit-logs
                            {--days= : Dias de retenção. Padrão: o configurado no módulo}
                            {--chunk=1000 : Tamanho do lote de exclusão}
                            {--dry-run : Apenas relata o que seria removido}';

    protected $description = 'Remove registros de auditoria do Customer Intelligence fora da janela de retenção';

    public function handle(): int
    {
        // `?:` cairia no padrao para --days=0, porque '0' e falsy em PHP.
        $informado = $this->option('days');
        $dias = $informado === null
            ? (int) config('customer-intelligence-internal.retention.audit_days', 730)
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

        $total = AuditLog::where('created_at', '<', $cutoff)->count();

        $this->components->info(sprintf(
            'Retenção de %d dias. Corte em %s. Registros fora da janela: %d.',
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

        $this->components->info($removidos.' registros de auditoria removidos.');

        return self::SUCCESS;
    }

    /**
     * Mesma estrategia do expurgo de eventos: seleciona as chaves e apaga por
     * `whereIn`. `DELETE ... LIMIT` nao e portavel, e carregar os modelos
     * estouraria a memoria num historico grande.
     */
    private function pruneInBatches(Carbon $cutoff, int $lote): int
    {
        $removidos = 0;

        do {
            $ids = AuditLog::where('created_at', '<', $cutoff)
                ->orderBy('id')
                ->limit($lote)
                ->pluck('id');

            if ($ids->isEmpty()) {
                break;
            }

            $removidos += AuditLog::whereIn('id', $ids)->delete();
        } while ($ids->count() === $lote);

        return $removidos;
    }
}
