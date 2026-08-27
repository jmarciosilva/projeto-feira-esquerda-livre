<?php

namespace App\CatalogIntelligence\DTOs;

use App\Models\Product;

/**
 * Um item semelhante, com o motivo junto.
 *
 * O score serve para ordenar; as razões são o que torna o resultado auditável.
 * Quem consumir isto deve poder responder "por que estes dois?" sem abrir o
 * código.
 */
final class SimilarProduct
{
    /**
     * @param  array<int, string>  $sharedConcepts
     * @param  array<int, MatchReason>  $reasons
     */
    public function __construct(
        public readonly Product $product,
        public readonly int $score,
        public readonly array $sharedConcepts,
        public readonly array $reasons,
    ) {}

    public function toArray(): array
    {
        return [
            'product_id' => $this->product->id,
            'name' => $this->product->name,
            'score' => $this->score,
            'shared_concepts' => $this->sharedConcepts,
            'reasons' => array_map(fn (MatchReason $r) => $r->description, $this->reasons),
        ];
    }
}
