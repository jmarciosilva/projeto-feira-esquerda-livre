<?php

namespace App\CatalogIntelligence\Enums;

/**
 * Por que um conceito foi considerado candidato.
 *
 * O tipo carrega duas coisas ao mesmo tempo: o peso no score e o direito de
 * virar associação persistida. Sem essa distinção, "o texto diz crochê" e
 * "crochê se relaciona com tricô" pesariam igual — e a segunda afirmação é
 * muito mais frágil que a primeira.
 */
enum MatchType: string
{
    /** O texto do item contém o nome canônico do conceito. */
    case ExactName = 'exact_name';

    /** O texto contém um termo (sinônimo, alias, termo comercial) do conceito. */
    case ExactTerm = 'exact_term';

    /** Alcançado por relação a partir de um conceito encontrado diretamente. */
    case Related = 'related';

    public function label(): string
    {
        return match ($this) {
            self::ExactName => 'Nome do conceito no texto',
            self::ExactTerm => 'Termo do conceito no texto',
            self::Related => 'Conceito relacionado',
        };
    }

    /** Evidência direta no texto, não inferida por travessia do grafo. */
    public function isDirect(): bool
    {
        return $this !== self::Related;
    }
}
