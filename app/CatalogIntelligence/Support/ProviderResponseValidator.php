<?php

namespace App\CatalogIntelligence\Support;

use App\CatalogIntelligence\DTOs\ListingSuggestion;
use App\CatalogIntelligence\Enums\ProviderResponseViolation;
use App\CatalogIntelligence\Enums\SuggestionSource;

/**
 * O que o tipo de retorno de `suggest()` não garante — dívida **B-4**.
 *
 * CAT-06D. A spec §3.3 declara `suggest(): ListingSuggestion` e **não diz o que
 * fazer com resposta fora do contrato**; a CAT-06A §3 registrou a lacuna e
 * mandou fechá-la junto dos contratos, não depois deles.
 *
 * ## O que o PHP já garante, e por isso não está aqui
 *
 * O tipo de retorno garante a **classe**. As propriedades tipadas garantem que
 * `suggestedName` seja `?string`, que `source` seja um `SuggestionSource` e que
 * `confidence` seja `?float`. Revalidar qualquer uma dessas coisas seria
 * cerimônia: se o valor chegasse errado, o construtor já teria lançado.
 *
 * ## O que ele não garante — e é exatamente esta lista
 *
 * | O que passa pelo tipo | Por quê |
 * |---|---|
 * | `shortDescription: '   '` | `?string` não olha o conteúdo |
 * | `keywords: ['a' => [1, 2]]` | `array` não olha o que tem dentro |
 * | `confidence: 42.0` | `?float` não tem faixa |
 * | `source: Internal` numa resposta externa | enum garante o conjunto, não o certo |
 *
 * O `@param array<int, string>` do DTO é documentação para a análise estática,
 * e análise estática não roda sobre a resposta de um serviço em produção.
 *
 * ## Devolve violações; nunca lança
 *
 * Mesma escolha do `NullCatalogAiProvider` e da degradação parcial da CAT-05F:
 * resposta ruim de provider é **evento previsto**, não excepcional. Lançar aqui
 * obrigaria a 06G a envolver a validação em `try` para descobrir a mesma coisa
 * que uma lista vazia já diz.
 *
 * A lista alimenta o **quarto estado** do desfecho de F-1 — *"provider
 * respondeu, resposta inválida"* (D-CAT-06B-1) —, e é por isso que ela devolve
 * **quais** violações, e não um booleano: a 06G precisa registrar o motivo, e
 * um `false` sozinho não é registrável.
 *
 * ## O que ficou deliberadamente de fora
 *
 * **Comprimento de texto.** Uma descrição de 50 KB é problema real, mas o
 * limite é o da coluna, e a coluna é da CAT-02. Trazer esse número para cá
 * acoplaria o validador ao schema e criaria uma segunda fonte para o mesmo
 * limite — quem escreve é a CAT-09, e é lá que ele pertence.
 *
 * **Conteúdo do texto** — PII, marcação, instrução. Não é aqui: PII de saída é
 * a C-2 (`FreeTextRedactor`, 06E), instrução é a S-1 (`PromptGuard`, 06F) e
 * marcação é a S-2, registrada no docblock de `ListingSuggestion`. Este
 * validador olha **forma**, não conteúdo.
 */
class ProviderResponseValidator
{
    /**
     * As violações desta resposta. Lista vazia = resposta utilizável.
     *
     * @return array<int, ProviderResponseViolation>
     */
    public function violacoes(ListingSuggestion $resposta): array
    {
        $violacoes = [];

        if ($this->temTextoEmBranco($resposta)) {
            $violacoes[] = ProviderResponseViolation::TextoEmBranco;
        }

        if (! $this->eListaDeTextos($resposta->keywords)) {
            $violacoes[] = ProviderResponseViolation::KeywordsMalformadas;
        }

        if (! $this->eListaDeTextos($resposta->missingInformation)) {
            $violacoes[] = ProviderResponseViolation::MissingInformationMalformada;
        }

        if ($resposta->confidence !== null && ($resposta->confidence < 0.0 || $resposta->confidence > 1.0)) {
            $violacoes[] = ProviderResponseViolation::ConfiancaForaDeFaixa;
        }

        if ($resposta->source !== SuggestionSource::External) {
            $violacoes[] = ProviderResponseViolation::ProcedenciaIncorreta;
        }

        return $violacoes;
    }

    /** Atalho para quem só precisa decidir se usa a resposta. */
    public function ehUtilizavel(ListingSuggestion $resposta): bool
    {
        return $this->violacoes($resposta) === [];
    }

    /**
     * Algum dos três campos de texto é string sem conteúdo?
     *
     * Nulo **não** é violação — é a resposta correta para "não tenho o que
     * propor aqui", e o docblock de `ListingSuggestion` diz isso com todas as
     * letras. O que não pode é a string existir e não dizer nada.
     */
    private function temTextoEmBranco(ListingSuggestion $resposta): bool
    {
        foreach ([$resposta->suggestedName, $resposta->shortDescription, $resposta->description] as $texto) {
            if ($texto !== null && trim($texto) === '') {
                return true;
            }
        }

        return false;
    }

    /**
     * Lista sequencial de strings não vazias?
     *
     * `array_is_list()` recusa o array associativo e o de índices furados —
     * `['a' => 'x']` e `[0 => 'x', 2 => 'y']` viram objeto em JSON e quebram
     * quem esperar posição. O resto recusa aninhamento, número e string vazia.
     *
     * @param  array<mixed>  $valores
     */
    private function eListaDeTextos(array $valores): bool
    {
        if (! array_is_list($valores)) {
            return false;
        }

        foreach ($valores as $valor) {
            if (! is_string($valor) || trim($valor) === '') {
                return false;
            }
        }

        return true;
    }
}
