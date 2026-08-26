<?php

namespace App\CatalogIntelligence\Enums;

/**
 * De onde veio este conhecimento.
 *
 * É a pergunta central da base: "Crochê" cadastrado por um administrador e
 * "Crochê" deduzido de um texto não são o mesmo fato, ainda que a palavra seja
 * a mesma. Sem proveniência a base vira um monte de afirmações igualmente
 * confiáveis — que é exatamente o que ela não pode ser.
 *
 * A confiança aqui é ORDINAL, não numérica. `trustLevel()` responde só "qual
 * das duas origens vale mais", que é a única coisa que a governança precisa
 * saber hoje. A coluna `confidence` continua nula: atribuir 0,7 a uma origem
 * agora seria inventar precisão que ninguém mediu.
 */
enum KnowledgeSource: string
{
    case HumanCurated = 'human_curated';
    case Seed = 'seed';
    case ApprovedListing = 'approved_listing';
    case Derived = 'derived';
    case ExternalAi = 'external_ai';

    public function label(): string
    {
        return match ($this) {
            self::HumanCurated => 'Curadoria humana',
            self::Seed => 'Base inicial',
            self::ApprovedListing => 'Cadastro aprovado',
            self::Derived => 'Derivado pelo sistema',
            self::ExternalAi => 'Sugerido por IA externa',
        };
    }

    /**
     * Maior vence. Usado para decidir se uma origem pode sobrescrever o que
     * outra já afirmou.
     */
    public function trustLevel(): int
    {
        return match ($this) {
            self::HumanCurated => 5,
            self::Seed => 4,
            self::ApprovedListing => 3,
            self::Derived => 2,
            self::ExternalAi => 1,
        };
    }

    public function outranks(self $other): bool
    {
        return $this->trustLevel() > $other->trustLevel();
    }

    public function atLeastAsTrustedAs(self $other): bool
    {
        return $this->trustLevel() >= $other->trustLevel();
    }

    /**
     * Origem que uma pessoa assinou. Só ela pode nascer aprovada sem passar
     * por revisão — ver KnowledgeStatus.
     */
    public function isHuman(): bool
    {
        return $this === self::HumanCurated || $this === self::Seed;
    }
}
