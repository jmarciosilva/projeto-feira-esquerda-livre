<?php

namespace App\CatalogIntelligence\Enums;

/**
 * De onde veio uma `ListingSuggestion`.
 *
 * Os dois casos são os que a §3.4 do documento arquitetural nomeia. Na CAT-05
 * **só `Internal` é alcançável**: a fase tem um caminho só, e nenhum provider
 * externo existe (D-CAT-05B-4).
 *
 * `External` entra aqui mesmo assim, e a razão é diferente da que fez a CAT-03
 * recusar `style` e `audience` em `KnowledgeEntryType`. Lá o caso extra não
 * tinha destinatário conhecido e convidaria a classificação arbitrária; aqui o
 * destinatário é a CAT-06, está escrito no roadmap, e a alternativa seria a
 * fase seguinte inventar uma string solta — que é o que este enum existe para
 * impedir. Quem consome uma sugestão precisa distinguir texto composto a
 * partir da base curada de texto vindo de terceiro, e essa distinção não pode
 * depender de comparar `'internal'` com `'internal'` em cada ponto.
 */
enum SuggestionSource: string
{
    /** Composta a partir do conhecimento interno aprovado. Sem chamada externa. */
    case Internal = 'internal';

    /** Veio de um provider externo, atrás do contrato da CAT-06. */
    case External = 'external';

    public function label(): string
    {
        return match ($this) {
            self::Internal => 'Conhecimento da Feira',
            self::External => 'Inteligência externa',
        };
    }

    /** A sugestão foi montada sem sair da aplicação? */
    public function isInternal(): bool
    {
        return $this === self::Internal;
    }
}
