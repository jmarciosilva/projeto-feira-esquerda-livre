<?php

namespace App\CatalogIntelligence\Providers;

use App\CatalogIntelligence\Contracts\CatalogAiProvider;
use App\CatalogIntelligence\DTOs\GuardedPrompt;
use App\CatalogIntelligence\DTOs\ListingSuggestion;
use App\CatalogIntelligence\Enums\SuggestionSource;
use App\CatalogIntelligence\Exceptions\CatalogAiProviderException;
use Closure;

/**
 * O provider de teste — determinístico, sem rede, sem relógio, sem aleatório.
 *
 * CAT-06D. Na CAT-06G passou a receber `GuardedPrompt`, a lançar a exceção
 * tipada da fronteira e a guardar os prompts que recebeu (D-CAT-06G-1,
 * D-CAT-06G-7). Existe para que o fallback inteiro seja testado sem nenhum
 * serviço de fora.
 *
 * ## Determinismo, e por que ele é a característica principal
 *
 * Mesma entrada, mesma saída, sempre — entre chamadas, entre execuções e entre
 * máquinas. Não há `rand()`, `now()` nem contador escondido influenciando o
 * conteúdo devolvido: quando nenhuma resposta é fixada, o texto é derivado do
 * `name` do canal de dado, e só dele.
 *
 * Um dublê que variasse produziria teste que passa hoje e falha na terça, e o
 * módulo perderia justamente a propriedade que permite afirmar qualquer coisa
 * sobre o comportamento do fallback.
 *
 * ## As situações do desfecho, e como chegar a cada uma
 *
 * | Situação | Como |
 * |---|---|
 * | Provider ausente | `FakeCatalogAiProvider::indisponivel()` |
 * | Provider responde bem | `::disponivel()` ou `::respondendo($s)` |
 * | Provider responde inválido | `::respondendo($s)` com um DTO fora do contrato |
 * | Provider falha | `::queFalha()` |
 * | Prazo esgotado (B-5) | `::queEsgotaOTempo()` |
 *
 * `queFalha()` e `queEsgotaOTempo()` são a exceção deliberada à regra do `Null`:
 * aquele **nunca** lança porque é caminho de produção; estes lançam porque
 * **simular falha é o trabalho deles**. Lançam `CatalogAiProviderException` — a
 * falha que um adaptador de verdade sinalizaria —, e não uma exceção qualquer,
 * que o assistente não trata como falha de provider.
 *
 * O prazo esgotado é simulado sem esperar: o Fake lança na hora a exceção que o
 * adaptador lançaria depois dos 8 segundos. O que se testa é o que o assistente
 * faz com ela, e não o relógio.
 *
 * ## Conta as chamadas e guarda os prompts
 *
 * `chamadas()` existe para provar o que **não** aconteceu: que a política
 * decidiu não consultar e, de fato, nada foi consultado. Uma asserção sobre o
 * veredito sozinha não distingue "não consultou" de "consultou e ignorou".
 *
 * `promptsRecebidos()` mostra o que chegou à fronteira — é por ele que os testes
 * da CAT-06G veem a redação e os canais do lado de quem recebe.
 */
final class FakeCatalogAiProvider implements CatalogAiProvider
{
    /** @var array<int, GuardedPrompt> */
    private array $prompts = [];

    /** @param  (Closure(): CatalogAiProviderException)|null  $falha */
    private function __construct(
        private readonly bool $disponivel,
        private readonly ?ListingSuggestion $resposta,
        private readonly ?Closure $falha,
    ) {}

    /** Disponível, respondendo o texto derivado do prompt. */
    public static function disponivel(): self
    {
        return new self(disponivel: true, resposta: null, falha: null);
    }

    /** Sem credencial: o segundo caminho normal de operação, ao lado do `Null`. */
    public static function indisponivel(): self
    {
        return new self(disponivel: false, resposta: null, falha: null);
    }

    /** Disponível, devolvendo exatamente esta sugestão — inclusive uma inválida. */
    public static function respondendo(ListingSuggestion $resposta): self
    {
        return new self(disponivel: true, resposta: $resposta, falha: null);
    }

    /** Disponível, mas a chamada quebra com a falha esperada da fronteira. */
    public static function queFalha(): self
    {
        return new self(
            disponivel: true,
            resposta: null,
            falha: fn () => new CatalogAiProviderException('falha simulada do provider'),
        );
    }

    /** Disponível, e o prazo da tentativa se esgota — B-5, sem esperar por ele. */
    public static function queEsgotaOTempo(): self
    {
        return new self(
            disponivel: true,
            resposta: null,
            falha: fn () => CatalogAiProviderException::tempoEsgotado(),
        );
    }

    public function isAvailable(): bool
    {
        return $this->disponivel;
    }

    public function suggest(GuardedPrompt $prompt): ListingSuggestion
    {
        $this->prompts[] = $prompt;

        if ($this->falha !== null) {
            throw ($this->falha)();
        }

        return $this->resposta ?? $this->derivadaDe($prompt);
    }

    /** Quantas vezes `suggest()` foi chamado nesta instância. */
    public function chamadas(): int
    {
        return count($this->prompts);
    }

    /** @return array<int, GuardedPrompt> Os prompts que chegaram a `suggest()`, na ordem. */
    public function promptsRecebidos(): array
    {
        return $this->prompts;
    }

    /**
     * A resposta padrão, derivada só do nome do item.
     *
     * `source: External` porque é isso que um provider de fora devolveria, e é
     * o que o `ProviderResponseValidator` exige — um dublê que se declarasse
     * `Internal` passaria nos testes da 06D e falharia no primeiro uso real.
     */
    private function derivadaDe(GuardedPrompt $prompt): ListingSuggestion
    {
        $nome = (string) ($prompt->data['name'] ?? '');

        return new ListingSuggestion(
            suggestedName: null,
            shortDescription: "Resumo sugerido para {$nome}.",
            description: "Descrição sugerida para {$nome}.",
            keywords: [mb_strtolower($nome)],
            missingInformation: [],
            source: SuggestionSource::External,
        );
    }
}
