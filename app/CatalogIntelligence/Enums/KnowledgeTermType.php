<?php

namespace App\CatalogIntelligence\Enums;

/**
 * Por que este termo aponta para o conceito.
 *
 * A distinção importa na hora de escrever texto: um sinônimo pode substituir o
 * nome canônico numa frase, um termo comercial é como o público procura, e um
 * alias costuma ser só grafia alternativa. Quem for gerar descrição precisa
 * saber qual é qual.
 */
enum KnowledgeTermType: string
{
    case Synonym = 'synonym';
    case Keyword = 'keyword';
    case Alias = 'alias';
    case CommercialTerm = 'commercial_term';

    public function label(): string
    {
        return match ($this) {
            self::Synonym => 'Sinônimo',
            self::Keyword => 'Palavra-chave',
            self::Alias => 'Grafia alternativa',
            self::CommercialTerm => 'Termo comercial',
        };
    }
}
