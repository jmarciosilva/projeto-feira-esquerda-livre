<?php

namespace App\CatalogIntelligence\Console;

use App\CatalogIntelligence\Actions\AssociateProductKnowledge;
use App\CatalogIntelligence\Actions\MatchProductKnowledge;
use App\CatalogIntelligence\DTOs\ProductKnowledgeInput;
use App\Models\Product;
use Illuminate\Console\Command;

/**
 * Associa itens do catálogo ao conhecimento aprovado, em lote.
 *
 * Vive num comando, e não numa migration, porque **migration muda esquema e
 * comando muda dado**. Um backfill dentro de migration roda sozinho no deploy,
 * sem ninguém olhar o resultado, e é irreversível na prática.
 *
 * `--dry-run` é o modo em que se deve conhecer o comando primeiro: mostra
 * exatamente o que seria gravado sem gravar nada. A decisão de persistir em
 * massa é humana e informada, tomada depois de ler o relatório.
 *
 * Não cria conceito, não aprova rascunho, não apaga associação humana e não
 * chama serviço externo. Roda de novo sem duplicar.
 */
class AssociateProductsCommand extends Command
{
    protected $signature = 'catalog-intelligence:associate-products
                            {--dry-run : Mostra o que seria associado, sem gravar nada}
                            {--product= : Analisa apenas um item, pelo id}
                            {--chunk=50 : Tamanho do lote}';

    protected $description = 'Associa itens do catálogo aos conceitos aprovados da base de conhecimento';

    public function handle(MatchProductKnowledge $matcher, AssociateProductKnowledge $associator): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $chunk = max(1, (int) $this->option('chunk'));

        $analisados = 0;
        $comCandidatos = 0;
        $comEvidenciaDireta = 0;
        $semNenhum = 0;
        $associados = 0;
        $jaExistentes = 0;
        $frequencia = [];

        $query = Product::query()->with('category')->orderBy('id');

        if ($id = $this->option('product')) {
            $query->where('id', (int) $id);
        }

        if ($dryRun) {
            $this->warn('Modo dry-run: nada será gravado.');
        }

        $query->chunkById($chunk, function ($produtos) use (
            $matcher, $associator, $dryRun,
            &$analisados, &$comCandidatos, &$comEvidenciaDireta, &$semNenhum,
            &$associados, &$jaExistentes, &$frequencia
        ) {
            foreach ($produtos as $produto) {
                $analisados++;

                $candidatos = $matcher(ProductKnowledgeInput::fromProduct($produto));

                if ($candidatos->isEmpty()) {
                    $semNenhum++;

                    continue;
                }

                $comCandidatos++;
                $diretos = $candidatos->filter(fn ($c) => $c->temEvidenciaDireta());

                if ($diretos->isNotEmpty()) {
                    $comEvidenciaDireta++;
                }

                foreach ($diretos as $candidato) {
                    $nome = $candidato->entry->name;
                    $frequencia[$nome] = ($frequencia[$nome] ?? 0) + 1;
                }

                if ($dryRun) {
                    continue;
                }

                $resultado = $associator($produto, $candidatos);
                $associados += $resultado['associados'];
                $jaExistentes += $resultado['ja_existentes'];
            }
        });

        arsort($frequencia);

        $this->newLine();
        $this->line('<info>Itens analisados:</info>            '.$analisados);
        $this->line('<info>Com algum candidato:</info>         '.$comCandidatos);
        $this->line('<info>Com evidência direta:</info>        '.$comEvidenciaDireta);
        $this->line('<info>Sem nenhum candidato:</info>        '.$semNenhum);

        if (! $dryRun) {
            $this->line('<info>Associações gravadas:</info>        '.$associados);
            $this->line('<info>Já existentes:</info>               '.$jaExistentes);
        }

        if ($frequencia !== []) {
            $this->newLine();
            $this->line('<comment>Conceitos mais encontrados (evidência direta):</comment>');
            foreach (array_slice($frequencia, 0, 15, true) as $nome => $n) {
                $this->line('  '.str_pad($nome, 26).$n);
            }
        }

        return self::SUCCESS;
    }
}
