<?php

namespace App\CatalogIntelligence\Actions;

use App\CatalogIntelligence\DTOs\KnowledgeCandidate;
use App\CatalogIntelligence\DTOs\MatchReason;
use App\CatalogIntelligence\DTOs\ProductKnowledgeInput;
use App\CatalogIntelligence\Enums\KnowledgeStatus;
use App\CatalogIntelligence\Enums\MatchType;
use App\CatalogIntelligence\Models\KnowledgeEntry;
use App\CatalogIntelligence\Models\KnowledgeRelation;
use App\CatalogIntelligence\Support\ProductTextNormalizer;
use App\CatalogIntelligence\Support\SimilarityScorer;
use Illuminate\Support\Collection;

/**
 * Dado o texto de um item, quais conceitos da base parecem se aplicar.
 *
 * **Não escreve nada.** Roda inteiro em memória e devolve candidatos; persistir
 * é decisão de outra Action. Essa separação é o que permite oferecer sugestão
 * durante um cadastro que ainda nem foi salvo.
 *
 * ## Como funciona
 *
 * 1. Normaliza o texto do item com o mesmo normalizador da base (CAT-03).
 * 2. Procura, por **frase inteira**, o nome canônico e os termos de cada
 *    conceito aprovado.
 * 3. A partir do que achou diretamente, caminha **um passo** pelas relações
 *    para trazer contexto — com peso muito menor.
 *
 * ## Só conhecimento aprovado
 *
 * Rascunho, rejeitado e inativo não entram. Um conceito que ninguém validou não
 * pode influenciar o que o sistema sugere a um lojista — é a razão de a CAT-03
 * ter criado status.
 *
 * ## Um passo, não travessia
 *
 * A expansão por relação para no primeiro salto. Dois saltos ligariam quase
 * tudo a quase tudo num grafo pequeno, e o contexto viraria ruído.
 *
 * ## Custo
 *
 * Três consultas, independentemente do tamanho do catálogo: conceitos
 * aprovados com termos, relações de saída e relações de entrada dos conceitos
 * encontrados. O casamento acontece em PHP sobre esse conjunto. Isso troca
 * memória por previsibilidade de query — adequado enquanto a base de
 * conhecimento for da ordem de centenas de conceitos, que é o horizonte
 * declarado desta fase.
 */
class MatchProductKnowledge
{
    public function __construct(
        private readonly ProductTextNormalizer $normalizer,
        private readonly SimilarityScorer $scorer,
    ) {}

    /**
     * @return Collection<int, KnowledgeCandidate> Ordenada por score, maior primeiro.
     */
    public function __invoke(ProductKnowledgeInput $input): Collection
    {
        $haystack = $this->normalizer->normalizedHaystack(...$input->camposTextuais());

        if ($haystack === '') {
            return collect();
        }

        $aprovados = KnowledgeEntry::query()
            ->where('status', KnowledgeStatus::Approved)
            ->with('terms')
            ->get();

        $diretos = $this->casarDiretamente($aprovados, $haystack);
        $relacionados = $this->expandirPorRelacao($aprovados, $diretos->keys()->all());

        return $this->montarCandidatos($aprovados, $diretos, $relacionados)
            ->sortByDesc(fn (KnowledgeCandidate $c) => $c->score)
            ->values();
    }

    /**
     * Evidências encontradas no próprio texto.
     *
     * @param  Collection<int, KnowledgeEntry>  $aprovados
     * @return Collection<int, array<int, MatchReason>> Indexada por id do conceito.
     */
    private function casarDiretamente(Collection $aprovados, string $haystack): Collection
    {
        $achados = collect();

        foreach ($aprovados as $entry) {
            $reasons = [];

            if ($this->normalizer->contemFrase($haystack, $entry->normalized_name)) {
                $reasons[] = new MatchReason(
                    MatchType::ExactName,
                    "O texto menciona {$entry->name}.",
                    $entry->name,
                );
            }

            foreach ($entry->terms as $term) {
                if ($this->normalizer->contemFrase($haystack, $term->normalized_term)) {
                    $reasons[] = new MatchReason(
                        MatchType::ExactTerm,
                        "O texto menciona \"{$term->term}\", que remete a {$entry->name}.",
                        $term->term,
                    );
                }
            }

            if ($reasons !== []) {
                $achados[$entry->id] = $reasons;
            }
        }

        return $achados;
    }

    /**
     * Um passo pelo grafo, nos dois sentidos.
     *
     * Nos dois porque a relação é gravada uma vez só: "Crochê é técnica de
     * Artesanato" existe como uma linha, e tanto quem chega por crochê quanto
     * quem chega por artesanato deve enxergar o vizinho.
     *
     * @param  Collection<int, KnowledgeEntry>  $aprovados
     * @param  array<int, int>  $idsDiretos
     * @return Collection<int, array<int, MatchReason>>
     */
    private function expandirPorRelacao(Collection $aprovados, array $idsDiretos): Collection
    {
        if ($idsDiretos === []) {
            return collect();
        }

        $porId = $aprovados->keyBy('id');
        $relacionados = collect();

        $relacoes = KnowledgeRelation::query()
            ->whereIn('from_entry_id', $idsDiretos)
            ->orWhereIn('to_entry_id', $idsDiretos)
            ->get();

        foreach ($relacoes as $relacao) {
            foreach ([[$relacao->from_entry_id, $relacao->to_entry_id], [$relacao->to_entry_id, $relacao->from_entry_id]] as [$origem, $destino]) {
                if (! in_array($origem, $idsDiretos, true) || in_array($destino, $idsDiretos, true)) {
                    continue;
                }

                // Conceito vizinho que não está aprovado não entra: o filtro de
                // status vale para o grafo também, não só para o casamento direto.
                if (! $porId->has($destino) || ! $porId->has($origem)) {
                    continue;
                }

                $relacionados[$destino] = array_merge($relacionados[$destino] ?? [], [
                    new MatchReason(
                        MatchType::Related,
                        "{$porId[$destino]->name} se relaciona com {$porId[$origem]->name}, encontrado no texto.",
                        $porId[$origem]->name,
                    ),
                ]);
            }
        }

        return $relacionados;
    }

    /**
     * @param  Collection<int, KnowledgeEntry>  $aprovados
     * @param  Collection<int, array<int, MatchReason>>  $diretos
     * @param  Collection<int, array<int, MatchReason>>  $relacionados
     * @return Collection<int, KnowledgeCandidate>
     */
    private function montarCandidatos(Collection $aprovados, Collection $diretos, Collection $relacionados): Collection
    {
        $porId = $aprovados->keyBy('id');
        $candidatos = collect();

        foreach ($diretos->keys()->merge($relacionados->keys())->unique() as $entryId) {
            $reasons = array_merge($diretos[$entryId] ?? [], $relacionados[$entryId] ?? []);

            $score = array_sum(array_map(
                fn (MatchReason $r) => $this->scorer->pesoDoMatch($r->type),
                $reasons,
            ));

            $candidatos[] = new KnowledgeCandidate($porId[$entryId], $score, $reasons);
        }

        return $candidatos;
    }
}
