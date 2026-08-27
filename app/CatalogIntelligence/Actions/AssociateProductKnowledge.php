<?php

namespace App\CatalogIntelligence\Actions;

use App\CatalogIntelligence\DTOs\KnowledgeCandidate;
use App\CatalogIntelligence\DTOs\ProductKnowledgeInput;
use App\CatalogIntelligence\Enums\KnowledgeSource;
use App\CatalogIntelligence\Support\SimilarityScorer;
use App\Models\Product;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * A única porta que grava em `catalog_product_knowledge`.
 *
 * A CAT-03 deixou esse pivot deliberadamente vazio. Esta Action é a primeira
 * autorizada a preenchê-lo — e o faz sob restrição, não por padrão.
 *
 * ## Candidato ≠ associação
 *
 * `MatchProductKnowledge` calcula e não grava. Aqui é o inverso: recebe
 * candidatos já calculados e decide quais merecem virar fato persistido. Manter
 * as duas coisas separadas é o que permite sugerir sem afirmar.
 *
 * ## A regra é conservadora de propósito
 *
 * Só vira associação o candidato com **evidência direta no texto** — nome
 * canônico ou termo do conceito. Conceito alcançado por relação nunca é
 * gravado, por mais alto que some no score.
 *
 * Falso negativo custa uma sugestão a menos. Falso positivo entra na base, é
 * lido depois como conhecimento e volta reforçando outros itens: o sistema
 * passa a confirmar o próprio engano. Os dois erros não têm o mesmo preço.
 *
 * ## Proveniência
 *
 * A associação automática grava `KnowledgeSource::Derived` — valor que já
 * existia no enum da CAT-03 e que descreve exatamente o caso: inferido pelo
 * sistema. Não foi preciso expandir o enum, e nenhuma proveniência nova foi
 * inventada.
 *
 * Associação humana (`human_curated`) **nunca é sobrescrita nem rebaixada** por
 * uma passagem automática: quando o par já existe, esta Action não toca nele.
 */
class AssociateProductKnowledge
{
    public function __construct(private readonly SimilarityScorer $scorer) {}

    /**
     * Persiste os candidatos elegíveis, sem duplicar.
     *
     * @param  Collection<int, KnowledgeCandidate>  $candidatos
     * @return array{associados: int, ignorados: int, ja_existentes: int}
     */
    public function __invoke(
        Product $product,
        Collection $candidatos,
        KnowledgeSource $source = KnowledgeSource::Derived,
    ): array {
        $associados = 0;
        $ignorados = 0;
        $jaExistentes = 0;

        $existentes = DB::table('catalog_product_knowledge')
            ->where('product_id', $product->id)
            ->pluck('knowledge_entry_id')
            ->all();

        foreach ($candidatos as $candidato) {
            if (! $this->scorer->podeVirarAssociacao($candidato->melhorTipo()) || ! $candidato->temEvidenciaDireta()) {
                $ignorados++;

                continue;
            }

            if (in_array($candidato->entry->id, $existentes, true)) {
                $jaExistentes++;

                continue;
            }

            try {
                DB::table('catalog_product_knowledge')->insert([
                    'product_id' => $product->id,
                    'knowledge_entry_id' => $candidato->entry->id,
                    'source' => $source->value,
                    // Score não é persistido: é derivado do texto atual do item
                    // e ficaria desatualizado no instante em que o lojista
                    // editasse a descrição. Recalcular é barato; invalidar não.
                    'confidence' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $associados++;
            } catch (QueryException $e) {
                // A UNIQUE do pivot venceu uma corrida. O par existe, que era o
                // objetivo — não é erro.
                if (! $this->parJaExiste($product->id, $candidato->entry->id)) {
                    throw $e;
                }
                $jaExistentes++;
            }
        }

        return [
            'associados' => $associados,
            'ignorados' => $ignorados,
            'ja_existentes' => $jaExistentes,
        ];
    }

    /** Conveniência: casa e associa num passo só. */
    public function paraProduto(Product $product, MatchProductKnowledge $matcher): array
    {
        $product->loadMissing('category');

        return $this($product, $matcher(ProductKnowledgeInput::fromProduct($product)));
    }

    private function parJaExiste(int $productId, int $entryId): bool
    {
        return DB::table('catalog_product_knowledge')
            ->where('product_id', $productId)
            ->where('knowledge_entry_id', $entryId)
            ->exists();
    }
}
