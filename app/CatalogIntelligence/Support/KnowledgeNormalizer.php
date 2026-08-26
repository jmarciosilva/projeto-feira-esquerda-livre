<?php

namespace App\CatalogIntelligence\Support;

use Illuminate\Support\Str;

/**
 * A única forma de produzir a chave de deduplicação de um conceito ou termo.
 *
 * Existe para que "Crochê", "crochê", "CROCHÊ", " Croche " e "croché" não virem
 * cinco conceitos. Fica numa classe só, e não espalhado em `Str::lower()` pelos
 * models e actions, porque a regra precisa ser uma coisa testável — se cada
 * ponto normalizar do seu jeito, a UNIQUE do banco deixa de significar o que
 * promete.
 *
 * ## A decisão sobre acentos
 *
 * A normalização **remove acentos**. Isso é uma escolha, com um custo real:
 * `Str::ascii()` reduz tanto "sabiá" quanto "sabia" a `sabia`, e em português
 * existem pares assim que são palavras diferentes.
 *
 * Aceitamos o custo por dois motivos. Primeiro, o dano prático é assimétrico:
 * dois conceitos distintos colidindo é raro e visível — a UNIQUE recusa o
 * segundo cadastro, e uma pessoa resolve; já o mesmo conceito duplicado por
 * causa de um acento é frequente, silencioso e envenena a base sem que ninguém
 * perceba. Segundo, nomes de técnica, material e tipo de item num marketplace
 * de artesanato ("crochê", "macramê", "cerâmica") são exatamente o caso em que
 * a grafia varia e o significado não.
 *
 * O acento não se perde: `name` guarda o texto como a pessoa escreveu, com
 * acentuação, e é ele que aparece na tela. A normalização produz apenas a
 * chave de comparação.
 */
class KnowledgeNormalizer
{
    /**
     * Chave de comparação: minúsculas, sem acento, sem pontuação supérflua e
     * com espaços colapsados.
     *
     * Devolve string vazia para entrada que não sobrou nada — quem chama
     * decide se isso é erro.
     */
    public function normalize(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        $value = Str::ascii($value);
        $value = Str::lower($value);

        // Hífen e apóstrofo viram espaço em vez de sumir: "bem-estar" e "bem
        // estar" devem coincidir, mas "bemestar" não é o que ninguém escreve.
        $value = preg_replace('/[\-\x{2013}\x{2014}\'`´]+/u', ' ', $value) ?? '';

        // O resto da pontuação some.
        $value = preg_replace('/[^a-z0-9 ]+/u', '', $value) ?? '';

        // Espaço interno colapsado, para " crochê   fino " bater com
        // "crochê fino".
        $value = preg_replace('/\s+/u', ' ', $value) ?? '';

        return trim($value);
    }

    /** Nome de exibição: só o excesso de espaço em branco é removido. */
    public function cleanDisplayName(?string $value): string
    {
        return trim(preg_replace('/\s+/u', ' ', (string) $value) ?? '');
    }

    /** Um nome só é utilizável se sobrar chave depois de normalizado. */
    public function isUsable(?string $value): bool
    {
        return $this->normalize($value) !== '';
    }
}
