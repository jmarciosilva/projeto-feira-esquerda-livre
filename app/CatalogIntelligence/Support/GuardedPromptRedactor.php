<?php

namespace App\CatalogIntelligence\Support;

use App\CatalogIntelligence\DTOs\GuardedPrompt;

/**
 * O prompt protegido na versão que pode sair da aplicação — C-2 composto com S-1.
 *
 * CAT-06G (D-CAT-06G-2). Recebe o `GuardedPrompt` do `PromptGuard` e devolve
 * outro, com o **conteúdo** dos dois canais não confiáveis passado pelo
 * `FreeTextRedactor`. É composição por fora: o guard continua sem ler conteúdo
 * (D-CAT-06F-4), o redator continua recebendo texto e devolvendo texto
 * (D-CAT-06B-2), e `GuardedPrompt` continua sendo só a forma.
 *
 * ## Depois do guard, e não antes
 *
 * `ListingContext` tem construtor privado e nenhuma porta para uma cópia
 * redigida — criá-la mudaria o DTO da CAT-05C. E redigir depois da classificação
 * mantém a ordem das duas perguntas: primeiro *de quem é este campo*, depois *o
 * que dele pode sair*. Quando o redator olha, os canais já estão separados, e ele
 * não tem como juntá-los.
 *
 * ## O que é redigido
 *
 * | Parte | Tratamento |
 * |---|---|
 * | `instruction` | **nunca** — é `ProviderInstruction`, um enum da aplicação, não texto |
 * | toda string de `context` e `data`, em qualquer profundidade | redigida |
 * | inteiro e decimal de `context` e `data` | redigidos pela forma escrita; só viram texto se continham dado pessoal |
 * | booleano e nulo | intactos |
 * | chaves estruturais (`name`, `knowledge`, `terms`, …) | intactas — são nomes do código |
 * | chaves de `known_attributes` | redigidas — ver abaixo |
 *
 * ## As chaves de `known_attributes` vêm de fora
 *
 * É o único mapa do prompt cujas **chaves** a aplicação não escreveu: chegam de
 * quem monta o contexto (dívida C-1), e o `ContextSanitizer` só derruba as que
 * estão numa lista de proibição — uma chave com um telefone dentro passa por ele.
 * Por isso a chave desse mapa é redigida como o valor.
 *
 * Duas chaves diferentes podem virar a mesma depois da redação. Quando isso
 * acontece **a primeira permanece e as seguintes saem**. Juntar os valores mudaria
 * o tipo do atributo, e numerar a chave inventaria texto que ninguém escreveu. Só
 * alcança quem descumpriu a C-1 pondo dado pessoal no nome do campo, e o caminho
 * interno continua recebendo o contexto original.
 *
 * ## Não lança, não registra
 *
 * Mesma escolha das duas peças que compõe: o redator falha fechado e em silêncio,
 * e registrar aqui seria gravar o texto que a classe existe para conter.
 */
class GuardedPromptRedactor
{
    /** O mapa do canal de dado cujas chaves são conteúdo, e não estrutura. */
    private const MAPA_DE_CHAVES_LIVRES = 'known_attributes';

    public function __construct(private readonly FreeTextRedactor $redator) {}

    public function __invoke(GuardedPrompt $prompt): GuardedPrompt
    {
        $data = $this->valores($prompt->data);

        if (is_array($data[self::MAPA_DE_CHAVES_LIVRES] ?? null)) {
            $data[self::MAPA_DE_CHAVES_LIVRES] = $this->chaves($data[self::MAPA_DE_CHAVES_LIVRES]);
        }

        return new GuardedPrompt(
            instruction: $prompt->instruction,
            context: $this->valores($prompt->context),
            data: $data,
        );
    }

    /**
     * @param  array<array-key, mixed>  $valores
     * @return array<array-key, mixed>
     */
    private function valores(array $valores): array
    {
        foreach ($valores as $chave => $valor) {
            $valores[$chave] = match (true) {
                is_string($valor) => $this->redator->redigir($valor),
                is_int($valor), is_float($valor) => $this->numero($valor),
                is_array($valor) => $this->valores($valor),
                default => $valor,
            };
        }

        return $valores;
    }

    /** Um número só deixa de ser número se a forma escrita dele era dado pessoal. */
    private function numero(int|float $numero): int|float|string
    {
        $escrito = (string) $numero;
        $redigido = $this->redator->redigir($escrito);

        return $redigido === $escrito ? $numero : $redigido;
    }

    /**
     * @param  array<array-key, mixed>  $mapa
     * @return array<array-key, mixed>
     */
    private function chaves(array $mapa): array
    {
        $redigido = [];

        foreach ($mapa as $chave => $valor) {
            $escrita = (string) $chave;
            $nova = $this->redator->redigir($escrita);
            $nova = $nova === $escrita ? $chave : $nova;

            if (! array_key_exists($nova, $redigido)) {
                $redigido[$nova] = $valor;
            }
        }

        return $redigido;
    }
}
