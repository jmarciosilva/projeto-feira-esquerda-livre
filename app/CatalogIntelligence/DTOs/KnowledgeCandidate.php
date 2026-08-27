<?php

namespace App\CatalogIntelligence\DTOs;

use App\CatalogIntelligence\Enums\MatchType;
use App\CatalogIntelligence\Models\KnowledgeEntry;

/**
 * Um conceito que o motor achou plausível para um item — e nada além disso.
 *
 * Candidato **não é** associação. Nada aqui está gravado no banco; o matcher
 * roda inteiro sem escrever uma linha. Quem decide persistir é a Action de
 * associação, e só para candidatos com evidência direta.
 *
 * @see \App\CatalogIntelligence\Actions\AssociateProductKnowledge
 */
final class KnowledgeCandidate
{
    /** @param array<int, MatchReason> $reasons */
    public function __construct(
        public readonly KnowledgeEntry $entry,
        public readonly int $score,
        public readonly array $reasons,
    ) {}

    /** O candidato tem alguma evidência direta no texto, e não só por relação? */
    public function temEvidenciaDireta(): bool
    {
        foreach ($this->reasons as $reason) {
            if ($reason->type->isDirect()) {
                return true;
            }
        }

        return false;
    }

    public function melhorTipo(): MatchType
    {
        $melhor = MatchType::Related;

        foreach ($this->reasons as $reason) {
            if ($reason->type === MatchType::ExactName) {
                return MatchType::ExactName;
            }
            if ($reason->type === MatchType::ExactTerm) {
                $melhor = MatchType::ExactTerm;
            }
        }

        return $melhor;
    }

    public function toArray(): array
    {
        return [
            'knowledge_entry_id' => $this->entry->id,
            'name' => $this->entry->name,
            'type' => $this->entry->type->value,
            'score' => $this->score,
            'direct' => $this->temEvidenciaDireta(),
            'reasons' => array_map(fn (MatchReason $r) => $r->toArray(), $this->reasons),
        ];
    }
}
