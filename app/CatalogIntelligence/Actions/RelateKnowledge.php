<?php

namespace App\CatalogIntelligence\Actions;

use App\CatalogIntelligence\Enums\KnowledgeRelationType;
use App\CatalogIntelligence\Models\KnowledgeEntry;
use App\CatalogIntelligence\Models\KnowledgeRelation;
use Illuminate\Database\QueryException;
use InvalidArgumentException;

/**
 * Liga dois conceitos, uma vez só e numa direção só.
 *
 * Relação de um conceito com ele mesmo é recusada: não acrescenta informação e
 * faria qualquer travessia futura girar em falso.
 */
class RelateKnowledge
{
    public function __invoke(
        KnowledgeEntry $from,
        KnowledgeEntry $to,
        KnowledgeRelationType $type,
        ?float $weight = null,
    ): KnowledgeRelation {
        if ($from->id === $to->id) {
            throw new InvalidArgumentException('Um conceito não se relaciona consigo mesmo.');
        }

        $existing = KnowledgeRelation::query()
            ->where('from_entry_id', $from->id)
            ->where('to_entry_id', $to->id)
            ->where('relation_type', $type)
            ->first();

        if ($existing) {
            return $existing;
        }

        try {
            return KnowledgeRelation::create([
                'from_entry_id' => $from->id,
                'to_entry_id' => $to->id,
                'relation_type' => $type,
                'weight' => $weight,
            ]);
        } catch (QueryException $e) {
            $winner = KnowledgeRelation::query()
                ->where('from_entry_id', $from->id)
                ->where('to_entry_id', $to->id)
                ->where('relation_type', $type)
                ->first();

            if (! $winner) {
                throw $e;
            }

            return $winner;
        }
    }
}
