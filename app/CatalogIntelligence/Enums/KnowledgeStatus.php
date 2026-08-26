<?php

namespace App\CatalogIntelligence\Enums;

/**
 * Conhecimento não vira verdade só porque apareceu em algum lugar.
 *
 * `Draft` é a porta de entrada de tudo que não foi assinado por uma pessoa —
 * inclusive do que a CAT-04 vier a derivar e do que uma IA externa vier a
 * sugerir. Só `Approved` pode ser reutilizado para ajudar o próximo lojista;
 * essa é a razão de o enum existir.
 */
enum KnowledgeStatus: string
{
    case Draft = 'draft';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Inactive = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Rascunho',
            self::Approved => 'Aprovado',
            self::Rejected => 'Rejeitado',
            self::Inactive => 'Inativo',
        };
    }

    /** Único estado que pode ser reutilizado como conhecimento da Feira. */
    public function isUsable(): bool
    {
        return $this === self::Approved;
    }
}
