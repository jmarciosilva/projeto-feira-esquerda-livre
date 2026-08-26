<?php

namespace App\CatalogIntelligence\Actions;

use App\CatalogIntelligence\Enums\KnowledgeTermType;
use App\CatalogIntelligence\Models\KnowledgeEntry;
use App\CatalogIntelligence\Models\KnowledgeTerm;
use App\CatalogIntelligence\Support\KnowledgeNormalizer;
use Illuminate\Database\QueryException;
use InvalidArgumentException;

/**
 * Acrescenta um termo a um conceito, sem duplicar.
 *
 * Separada de CreateOrUpdateKnowledge de propósito: conceito e termo têm ciclos
 * de vida diferentes, e juntar as duas coisas produziria uma Action que faz
 * tudo e por isso não garante nada bem.
 */
class AttachKnowledgeTerm
{
    public function __construct(private readonly KnowledgeNormalizer $normalizer) {}

    public function __invoke(KnowledgeEntry $entry, string $term, KnowledgeTermType $type): KnowledgeTerm
    {
        $display = $this->normalizer->cleanDisplayName($term);
        $normalized = $this->normalizer->normalize($term);

        if ($normalized === '') {
            throw new InvalidArgumentException('Termo vazio depois de normalizado: '.var_export($term, true));
        }

        $existing = KnowledgeTerm::query()
            ->where('knowledge_entry_id', $entry->id)
            ->where('normalized_term', $normalized)
            ->first();

        if ($existing) {
            return $existing;
        }

        try {
            $model = new KnowledgeTerm([
                'knowledge_entry_id' => $entry->id,
                'term' => $display,
                'type' => $type,
            ]);
            $model->normalized_term = $normalized;
            $model->save();

            return $model;
        } catch (QueryException $e) {
            $winner = KnowledgeTerm::query()
                ->where('knowledge_entry_id', $entry->id)
                ->where('normalized_term', $normalized)
                ->first();

            if (! $winner) {
                throw $e;
            }

            return $winner;
        }
    }
}
