<?php

namespace App\CatalogIntelligence\Enums;

/**
 * Como um conceito se liga a outro.
 *
 * Quatro tipos, não dezenas. Cada um responde a uma pergunta que o catálogo
 * real faz:
 *
 *   technique_of  Crochê é técnica de Artesanato
 *   used_in       Crochê é usado em Almofada
 *   belongs_to    Almofada pertence a Decoração
 *   related_to    Artesanato se relaciona com Feito à mão
 *
 * `related_to` lê-se como simétrico, mas é gravado numa direção só: duplicar o
 * par criaria duas fontes da mesma verdade, e mantê-las sincronizadas seria
 * trabalho sem retorno. Quem percorrer o grafo olha os dois lados.
 */
enum KnowledgeRelationType: string
{
    case RelatedTo = 'related_to';
    case TechniqueOf = 'technique_of';
    case UsedIn = 'used_in';
    case BelongsTo = 'belongs_to';

    public function label(): string
    {
        return match ($this) {
            self::RelatedTo => 'Relacionado a',
            self::TechniqueOf => 'Técnica de',
            self::UsedIn => 'Usado em',
            self::BelongsTo => 'Pertence a',
        };
    }

    /** Faz sentido ler nos dois sentidos ao percorrer o grafo. */
    public function isSymmetric(): bool
    {
        return $this === self::RelatedTo;
    }
}
