<?php

namespace App\CatalogIntelligence\DTOs;

use App\CatalogIntelligence\Enums\MatchType;

/**
 * Uma evidência isolada de por que algo foi considerado semelhante.
 *
 * Guarda o tipo (legível por código, para peso e elegibilidade) e o texto
 * (legível por gente, para a tela e para a curadoria). Devolver só um número
 * tornaria impossível responder "por quê?", que é a pergunta que esta fase
 * inteira existe para responder.
 */
final class MatchReason
{
    public function __construct(
        public readonly MatchType $type,
        public readonly string $description,
        public readonly ?string $matchedText = null,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'type' => $this->type->value,
            'description' => $this->description,
            'matched_text' => $this->matchedText,
        ], fn ($v) => $v !== null);
    }

    public function __toString(): string
    {
        return $this->description;
    }
}
