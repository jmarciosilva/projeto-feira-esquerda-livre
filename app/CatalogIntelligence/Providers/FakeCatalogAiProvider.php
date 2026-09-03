<?php

namespace App\CatalogIntelligence\Providers;

use App\CatalogIntelligence\Contracts\CatalogAiProvider;
use App\CatalogIntelligence\DTOs\ListingContext;
use App\CatalogIntelligence\DTOs\ListingSuggestion;
use App\CatalogIntelligence\Enums\SuggestionSource;

/**
 * O provider de teste — determinístico, sem rede, sem relógio, sem aleatório.
 *
 * CAT-06D. Existe para que a **06G** possa testar o fallback inteiro sem
 * nenhum serviço de fora: as quatro situações do desfecho de F-1
 * (D-CAT-06B-1) são todas alcançáveis daqui.
 *
 * ## Determinismo, e por que ele é a característica principal
 *
 * Mesma entrada, mesma saída, sempre — entre chamadas, entre execuções e entre
 * máquinas. Não há `rand()`, `now()` nem contador escondido influenciando o
 * conteúdo devolvido: quando nenhuma resposta é fixada, o texto é derivado do
 * `name` do contexto, e só dele.
 *
 * Um dublê que variasse produziria teste que passa hoje e falha na terça, e o
 * módulo perderia justamente a propriedade que permite afirmar qualquer coisa
 * sobre o comportamento do fallback.
 *
 * ## As quatro situações, e como chegar a cada uma
 *
 * | Situação (F-1) | Como |
 * |---|---|
 * | Provider ausente | `FakeCatalogAiProvider::indisponivel()` |
 * | Provider responde bem | `new FakeCatalogAiProvider` ou `::respondendo($s)` |
 * | Provider responde inválido | `::respondendo($s)` com um DTO fora do contrato |
 * | Provider falha | `::queFalha()` — o único caminho que lança, e de propósito |
 *
 * `queFalha()` é a exceção deliberada à regra do `Null`: aquele **nunca** lança
 * porque é caminho de produção; este lança porque **simular falha é o trabalho
 * dele**, e sem isso a 06G não teria como exercitar o terceiro estado.
 *
 * ## Conta as chamadas
 *
 * `chamadas()` existe para a 06G provar o que **não** aconteceu: que a política
 * decidiu não consultar e, de fato, nada foi consultado. Uma asserção sobre o
 * veredito sozinha não distingue "não consultou" de "consultou e ignorou".
 */
final class FakeCatalogAiProvider implements CatalogAiProvider
{
    private int $chamadas = 0;

    private function __construct(
        private readonly bool $disponivel,
        private readonly ?ListingSuggestion $resposta,
        private readonly bool $falha,
    ) {}

    /** Disponível, respondendo o texto derivado do contexto. */
    public static function disponivel(): self
    {
        return new self(disponivel: true, resposta: null, falha: false);
    }

    /** Sem credencial: o segundo caminho normal de operação, ao lado do `Null`. */
    public static function indisponivel(): self
    {
        return new self(disponivel: false, resposta: null, falha: false);
    }

    /** Disponível, devolvendo exatamente esta sugestão — inclusive uma inválida. */
    public static function respondendo(ListingSuggestion $resposta): self
    {
        return new self(disponivel: true, resposta: $resposta, falha: false);
    }

    /** Disponível, mas a chamada quebra — o terceiro estado do desfecho de F-1. */
    public static function queFalha(): self
    {
        return new self(disponivel: true, resposta: null, falha: true);
    }

    public function isAvailable(): bool
    {
        return $this->disponivel;
    }

    public function suggest(ListingContext $context): ListingSuggestion
    {
        $this->chamadas++;

        if ($this->falha) {
            throw new \RuntimeException('falha simulada do provider');
        }

        return $this->resposta ?? $this->derivadaDe($context);
    }

    /** Quantas vezes `suggest()` foi chamado nesta instância. */
    public function chamadas(): int
    {
        return $this->chamadas;
    }

    /**
     * A resposta padrão, derivada só do nome do item.
     *
     * `source: External` porque é isso que um provider de fora devolveria, e é
     * o que o `ProviderResponseValidator` exige — um dublê que se declarasse
     * `Internal` passaria nos testes da 06D e falharia no primeiro uso real.
     */
    private function derivadaDe(ListingContext $context): ListingSuggestion
    {
        return new ListingSuggestion(
            suggestedName: null,
            shortDescription: "Resumo sugerido para {$context->name}.",
            description: "Descrição sugerida para {$context->name}.",
            keywords: [mb_strtolower($context->name)],
            missingInformation: [],
            source: SuggestionSource::External,
        );
    }
}
