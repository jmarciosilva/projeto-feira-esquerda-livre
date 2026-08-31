<?php

namespace App\Console\Commands;

use App\Actions\Catalog\BackfillOfferContent as BackfillAction;
use Illuminate\Console\Command;

/**
 * CAT-DOM-02D — executa o backfill da estrutura de conteúdo por oferta.
 *
 * Casca fina: quem decide é `Actions\Catalog\BackfillOfferContent`. Aqui ficam
 * só os modos, os guards de operação consciente e a impressão das métricas.
 *
 * ## Por que dois modos, e não um só
 *
 * `--inicial` é **conservador e aditivo**: destino vazio é populado, destino
 * preenchido é preservado. É a execução da 02D, e reexecutá-la não muda nada.
 *
 * `--reconciliar` é a execução **única** que roda imediatamente antes de a 02E
 * trocar readers e writers, para eliminar o drift acumulado na janela entre as
 * duas fases. Ela **substitui e apaga**, e por isso não é o padrão de nada.
 *
 * ## Por que a reconciliação exige confirmação
 *
 * Ela só é segura enquanto nenhum writer da 02E existir — nesse intervalo,
 * `product_offers.images` e `product_offer_faqs` são propriedade exclusiva
 * deste comando, e apagar suas linhas não destrói trabalho de ninguém (D11-C).
 * Depois do primeiro writer legítimo, o mesmo `delete` passaria a apagar
 * arquivo enviado por um lojista.
 *
 * Essa premissa **não é detectável pelo código**: não existe marcador de
 * proveniência, e inventar um seria fingir uma certeza que não temos. Então ela
 * é declarada por quem executa — `--confirmar-sem-writers-02e` —, e o comando
 * se recusa a rodar de forma não interativa sem ela. Deliberadamente nunca é
 * chamado por migration, deploy, boot ou scheduler.
 */
class BackfillOfferContent extends Command
{
    protected $signature = 'catalog:backfill-offer-content
        {--inicial : Popula o destino vazio sem sobrescrever nada (execução da CAT-DOM-02D)}
        {--reconciliar : Sincroniza o destino com a origem legada, substituindo e apagando (pré-cutover da 02E)}
        {--confirmar-sem-writers-02e : Declara que nenhum writer da CAT-DOM-02E foi habilitado; obrigatório em --reconciliar}
        {--simular : Apenas mede e relata, sem escrever no banco nem no disco}';

    protected $description = 'CAT-DOM-02D — projeta imagens, FAQ e contexto de oferta do legado para a estrutura por oferta';

    public function handle(BackfillAction $backfill): int
    {
        $inicial = (bool) $this->option('inicial');
        $reconciliar = (bool) $this->option('reconciliar');

        if ($inicial === $reconciliar) {
            $this->error('Escolha exatamente um modo: --inicial ou --reconciliar.');

            return self::INVALID;
        }

        $modo = $reconciliar ? BackfillAction::MODO_RECONCILIAR : BackfillAction::MODO_INICIAL;
        $simular = (bool) $this->option('simular');

        if ($reconciliar && ! $simular && ! $this->confirmarAusenciaDeWriters()) {
            return self::FAILURE;
        }

        $this->info(sprintf('Modo: %s%s', $modo, $simular ? ' (simulação)' : ''));

        $resultado = $backfill($modo, $simular);

        $this->imprimirMetricas($resultado['metricas']);

        $problemas = $this->verificarIntegridade($backfill, $modo, $resultado['metricas']);

        foreach ($resultado['erros'] as $erro) {
            $this->error("falha: {$erro}");
        }

        if ($resultado['erros'] !== [] || $problemas !== []) {
            $this->newLine();
            $this->error('Integridade não fechou. Nada além do relatado foi alterado.');

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Integridade verificada.');

        return self::SUCCESS;
    }

    /**
     * A precondição da reconciliação destrutiva, declarada e não adivinhada.
     */
    private function confirmarAusenciaDeWriters(): bool
    {
        if ($this->option('confirmar-sem-writers-02e')) {
            return true;
        }

        $this->warn('A reconciliação substitui a projeção da oferta e apaga as cópias antigas.');
        $this->warn('Isso só é seguro enquanto NENHUM writer da CAT-DOM-02E estiver habilitado (D11-C).');

        if (! $this->input->isInteractive()) {
            $this->error('Execução não interativa exige --confirmar-sem-writers-02e.');

            return false;
        }

        return (bool) $this->confirm('Confirma que nenhum writer da CAT-DOM-02E foi habilitado?', false);
    }

    /** @param  array<string,int>  $metricas */
    private function imprimirMetricas(array $metricas): void
    {
        $this->newLine();
        $this->table(
            ['métrica', 'valor'],
            collect($metricas)->map(fn ($v, $k) => [$k, $v])->values()->all(),
        );
    }

    /**
     * @param  array<string,int>  $metricas
     * @return list<string>
     */
    private function verificarIntegridade(BackfillAction $backfill, string $modo, array $metricas): array
    {
        $problemas = [];

        // O invariante do §17, verificado sobre os paths reais e não por
        // contagem de linhas: um único arquivo compartilhado entre o produto e
        // a oferta faria o lojista apagar a imagem do catálogo.
        foreach ($backfill->pathsCompartilhados() as $compartilhado) {
            $problemas[] = "path compartilhado entre produto e oferta: {$compartilhado}";
        }

        if ($modo === BackfillAction::MODO_RECONCILIAR) {
            // Paridade exata só é exigida da reconciliação. O modo inicial é
            // deliberadamente conservador e pode terminar com destino preservado
            // divergindo da origem — é o drift que a reconciliação resolve.
            foreach ($backfill->divergenciasDeFaq() as $divergencia) {
                $problemas[] = "FAQ sem paridade: {$divergencia}";
            }

            if ($metricas['faq_nao_resolvidas'] > 0) {
                $problemas[] = sprintf(
                    '%d FAQ(s) legada(s) não resolvida(s) — produto com 0 ou >1 ofertas. O cutover da 02E fica bloqueado.',
                    $metricas['faq_nao_resolvidas'],
                );
            }
        }

        foreach ($problemas as $problema) {
            $this->error($problema);
        }

        return $problemas;
    }
}
