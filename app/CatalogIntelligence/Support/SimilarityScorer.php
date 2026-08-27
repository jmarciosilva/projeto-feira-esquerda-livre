<?php

namespace App\CatalogIntelligence\Support;

use App\CatalogIntelligence\Enums\KnowledgeSource;
use App\CatalogIntelligence\Enums\MatchType;

/**
 * A única fonte dos pesos da similaridade.
 *
 * Existe para que não haja `+10` numa classe e `+5` noutra. Quando alguém
 * perguntar por que um item ficou à frente de outro, a resposta inteira está
 * neste arquivo.
 *
 * ## Os números não são medidos — são ordenados
 *
 * Nenhum peso aqui saiu de experimento. Eles codificam uma **ordem de
 * confiança**, que é o que temos hoje para afirmar com honestidade:
 *
 *   o texto contém o nome do conceito   >  contém um sinônimo dele
 *   evidência direta no texto            >  conceito alcançado por relação
 *   conceito confirmado por pessoa       >  conceito associado automaticamente
 *
 * Por isso o score serve para **ordenar**, não para ser lido como
 * porcentagem. "87,3% de similaridade" seria falsa ciência; "12 pontos, por
 * técnica compartilhada e atributo compartilhado" é auditável.
 *
 * Os valores são inteiros e espaçados de propósito: dá para intercalar um caso
 * novo no futuro sem reescrever a escala.
 */
class SimilarityScorer
{
    // ── Produto → conhecimento ───────────────────────────────────────────────

    /** O texto do item contém o nome canônico do conceito. Evidência mais forte. */
    public const PESO_NOME_EXATO = 10;

    /**
     * O texto contém um termo do conceito. Abaixo do nome canônico por um
     * passo: sinônimo e termo comercial são caminhos legítimos até o conceito,
     * mas indiretos.
     */
    public const PESO_TERMO_EXATO = 8;

    /**
     * Conceito alcançado por relação a partir de outro que foi encontrado
     * diretamente. Deliberadamente muito abaixo dos diretos: "crochê se
     * relaciona com tricô" não torna um tapete de crochê uma peça de tricô.
     * É contexto, não fato.
     */
    public const PESO_RELACIONADO = 3;

    // ── Produto → produto ────────────────────────────────────────────────────

    /** Conceito compartilhado em que os dois lados foram confirmados por pessoa. */
    public const PESO_CONCEITO_CONFIRMADO = 6;

    /**
     * Conceito compartilhado em que ao menos um lado veio de associação
     * automática. Vale menos para que erro automático não se amplifique: se
     * uma associação fraca virasse prova de semelhança, o sistema passaria a
     * confirmar o próprio engano.
     */
    public const PESO_CONCEITO_DERIVADO = 4;

    /**
     * Mesma categoria pública. Reforço pequeno de propósito — categoria é
     * navegação comercial, não conhecimento, e sozinha aproxima itens que só
     * dividem a prateleira.
     */
    public const PESO_MESMA_CATEGORIA = 2;

    public function pesoDoMatch(MatchType $tipo): int
    {
        return match ($tipo) {
            MatchType::ExactName => self::PESO_NOME_EXATO,
            MatchType::ExactTerm => self::PESO_TERMO_EXATO,
            MatchType::Related => self::PESO_RELACIONADO,
        };
    }

    /**
     * Peso de um conceito que dois itens têm em comum.
     *
     * Confirmado só quando **os dois lados** foram assinados por pessoa: basta
     * um lado automático para o par inteiro valer menos.
     */
    public function pesoDoConceitoCompartilhado(KnowledgeSource $a, KnowledgeSource $b): int
    {
        return $a->isHuman() && $b->isHuman()
            ? self::PESO_CONCEITO_CONFIRMADO
            : self::PESO_CONCEITO_DERIVADO;
    }

    public function pesoDaMesmaCategoria(): int
    {
        return self::PESO_MESMA_CATEGORIA;
    }

    /**
     * Um candidato só pode virar associação persistida com evidência direta no
     * texto.
     *
     * Falso negativo é aceitável; falso positivo não. Deixar de associar um
     * conceito custa uma sugestão a menos; afirmar conhecimento errado
     * contamina a base e reaparece depois como se fosse verdade.
     */
    public function podeVirarAssociacao(MatchType $tipo): bool
    {
        return $tipo->isDirect();
    }
}
