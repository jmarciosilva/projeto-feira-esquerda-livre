<?php

namespace App\CatalogIntelligence\DTOs;

use App\Models\Product;

/**
 * O que o motor precisa saber de um item para procurar conhecimento.
 *
 * Existe para que o matcher funcione com um item que **ainda não foi salvo** —
 * o cadastro em andamento das fases seguintes é exatamente esse caso. Amarrar o
 * motor ao model Eloquent obrigaria a existir uma linha no banco antes de
 * qualquer sugestão, o que é o contrário do que a trilha quer.
 *
 * Carrega só campo textual de catálogo. Nada de preço, estoque, dono ou
 * qualquer dado de gestão: eles não ajudam a decidir o que o item é.
 */
final class ProductKnowledgeInput
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $shortDescription = null,
        public readonly ?string $description = null,
        public readonly ?string $categoryName = null,
        public readonly ?int $productId = null,
        public readonly ?int $categoryId = null,
    ) {}

    public static function fromProduct(Product $product): self
    {
        return new self(
            name: (string) $product->name,
            shortDescription: $product->short_description,
            description: $product->description,
            categoryName: $product->relationLoaded('category') ? $product->category?->name : null,
            productId: $product->id,
            categoryId: $product->category_id,
        );
    }

    /**
     * Os campos que alimentam a busca, na ordem em que serão concatenados.
     *
     * O nome da categoria entra porque é vocabulário de catálogo legítimo
     * ("Artesanato", "Decoração") e costuma nomear o conceito melhor que a
     * descrição do lojista.
     *
     * @return array<int, string>
     */
    public function camposTextuais(): array
    {
        return array_values(array_filter([
            $this->name,
            $this->shortDescription,
            $this->description,
            $this->categoryName,
        ], fn ($c) => $c !== null && trim($c) !== ''));
    }
}
