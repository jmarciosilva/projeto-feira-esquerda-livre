<?php

namespace App\CatalogIntelligence\Support;

/**
 * Prepara o texto de um item para ser comparado com a base de conhecimento.
 *
 * **Não normaliza por conta própria.** Delega ao `KnowledgeNormalizer`, o mesmo
 * que produziu `normalized_name` e `normalized_term` na CAT-03. Se houvesse
 * duas regras, "Crochê" no texto do lojista e `croche` na base deixariam de se
 * encontrar por um detalhe de acento — e o bug seria invisível, porque cada
 * lado estaria "certo" segundo a sua própria regra.
 *
 * O que esta classe acrescenta é o que só faz sentido do lado do produto:
 * juntar os campos textuais, cercar o resultado com espaços para permitir busca
 * por frase inteira, e oferecer tokens para inspeção.
 *
 * ## Frase, não token
 *
 * A comparação é feita por **frase normalizada contida no texto normalizado**,
 * e não quebrando tudo em palavras soltas. Conceitos do catálogo são compostos
 * — "feito à mão", "ervas medicinais", "economia solidária" — e quebrar por
 * espaço destruiria justamente o que os torna conceitos. Na auditoria dos 75
 * itens reais isso apareceu de forma concreta: "solidária" ocorre 10 vezes, mas
 * sempre em "Consultoria Solidária" e "Tecnologia Solidária"; nenhuma delas é
 * economia solidária, e a busca por frase acerta ao não marcar nenhuma.
 *
 * Não há stemming nem NLP: reduzir "tapetes" a "tapete" exigiria regras de
 * português que erram em casos suficientes para não valerem o risco nesta fase.
 */
class ProductTextNormalizer
{
    /**
     * Palavras que aparecem muito e não distinguem nada.
     *
     * A lista é curta e serve só à inspeção por tokens — o casamento por frase
     * não depende dela. Foi montada a partir da contagem real dos 75 itens do
     * catálogo, onde "para" (52), "expositor" (46) e "demonstração" (28)
     * lideram sem dizer nada sobre o que o item é.
     */
    private const STOPWORDS = [
        'a', 'ao', 'aos', 'as', 'com', 'da', 'das', 'de', 'do', 'dos', 'e', 'em',
        'na', 'nas', 'no', 'nos', 'o', 'os', 'ou', 'para', 'por', 'que', 'sem',
        'um', 'uma', 'uns', 'umas',
    ];

    public function __construct(private readonly KnowledgeNormalizer $normalizer) {}

    /**
     * Texto normalizado do item, cercado por espaços.
     *
     * As bordas existem para que `str_contains($texto, " croche ")` só case com
     * a palavra inteira — sem elas, "crochê" seria encontrado dentro de
     * "crocheteiro" e a evidência seria falsa.
     */
    public function normalizedHaystack(string ...$campos): string
    {
        $juntos = implode(' ', array_filter($campos, fn ($c) => trim((string) $c) !== ''));

        $normalizado = $this->normalizer->normalize($juntos);

        return $normalizado === '' ? '' : ' '.$normalizado.' ';
    }

    /** A agulha correspondente: a mesma normalização, com as mesmas bordas. */
    public function normalizedNeedle(string $termo): string
    {
        $normalizado = $this->normalizer->normalize($termo);

        return $normalizado === '' ? '' : ' '.$normalizado.' ';
    }

    /**
     * O texto contém esta frase como unidade?
     *
     * Frase vazia devolve `false` em vez de casar com tudo — um termo que
     * sobrou vazio depois de normalizado não é evidência de nada.
     */
    public function contemFrase(string $haystack, string $fraseNormalizada): bool
    {
        if ($fraseNormalizada === '' || $haystack === '') {
            return false;
        }

        return str_contains($haystack, ' '.$fraseNormalizada.' ');
    }

    /**
     * Tokens úteis do texto, sem stopwords e sem palavras de uma letra.
     *
     * Não participa do casamento; existe para inspeção, diagnóstico e para as
     * fases seguintes decidirem o que fazer com o vocabulário do lojista.
     *
     * @return array<int, string>
     */
    public function tokens(string ...$campos): array
    {
        $normalizado = trim($this->normalizedHaystack(...$campos));

        if ($normalizado === '') {
            return [];
        }

        $tokens = array_filter(
            explode(' ', $normalizado),
            fn (string $t) => mb_strlen($t) > 1 && ! in_array($t, self::STOPWORDS, true),
        );

        return array_values(array_unique($tokens));
    }
}
