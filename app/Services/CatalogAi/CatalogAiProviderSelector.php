<?php

namespace App\Services\CatalogAi;

use App\CatalogIntelligence\Contracts\CatalogAiProvider;
use App\CatalogIntelligence\Providers\NullCatalogAiProvider;

/**
 * Quem responde por `CatalogAiProvider` neste ambiente — CAT-10A.
 *
 * Lê `config('services.catalog_ai')` a cada resolução e devolve o adaptador real só
 * quando tudo o que ele precisa está presente e é válido. Nas condições previstas
 * abaixo devolve o `NullCatalogAiProvider`, e o assistente segue com
 * `ProviderUnavailable` — o estado normal de operar sem IA externa (D-CAT-06B-5).
 *
 * | Condição | Resultado |
 * |---|---|
 * | `enabled` não é verdadeiro | `Null` |
 * | `provider` não é `openai` | `Null` |
 * | `api_key` ou `model` ausente ou em branco | `Null` |
 * | `timeout` não numérico ou ≤ 0 | `Null` |
 * | `timeout` ausente | 8 s |
 * | `timeout` acima de 8 | limitado a 8 s (B-5) |
 *
 * ## Não captura nada
 *
 * As condições são **verificadas**, não descobertas por exceção. Não há `try` nesta
 * classe: um defeito — `TypeError`, `Error`, `RuntimeException` de quem for
 * construído — sobe como defeito, e não vira "sem provider" em silêncio. Um teste
 * trava a ausência de `try` e `catch` neste arquivo.
 *
 * ## Sem log
 *
 * Configuração inválida não registra nada: a tela do lojista já diz que a sugestão
 * saiu só com a inteligência interna, e registrar a cada resolução seria ruído
 * perto da chave.
 */
final class CatalogAiProviderSelector
{
    /** O prazo máximo da tentativa externa, em segundos (B-5, D-CAT-06G-5). */
    public const PRAZO_MAXIMO = 8;

    /** O único provider real suportado nesta fase. */
    private const PROVIDER_SUPORTADO = 'openai';

    public function resolve(): CatalogAiProvider
    {
        $config = config('services.catalog_ai');
        $config = is_array($config) ? $config : [];

        $chave = $this->texto($config['api_key'] ?? null);
        $modelo = $this->texto($config['model'] ?? null);
        $prazo = $this->prazo($config['timeout'] ?? null);

        if (! $this->ligado($config['enabled'] ?? null)
            || ! $this->suportado($config['provider'] ?? null)
            || $chave === null
            || $modelo === null
            || $prazo === null) {
            return new NullCatalogAiProvider;
        }

        return new OpenAiCatalogAiProvider($chave, $modelo, $prazo);
    }

    /** Só o verdadeiro liga: `true`, `"true"`, `"1"`, `"on"`. Ausente ou ambíguo, não. */
    private function ligado(mixed $valor): bool
    {
        return filter_var($valor, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) === true;
    }

    private function suportado(mixed $valor): bool
    {
        return is_string($valor) && strtolower(trim($valor)) === self::PROVIDER_SUPORTADO;
    }

    /** Texto não vazio, ou nulo quando não há o que usar. */
    private function texto(mixed $valor): ?string
    {
        return is_string($valor) && trim($valor) !== '' ? trim($valor) : null;
    }

    /** O prazo em segundos, limitado ao máximo — ou nulo, quando o valor configurado é inválido. */
    private function prazo(mixed $valor): ?float
    {
        if ($valor === null) {
            return (float) self::PRAZO_MAXIMO;
        }

        if (is_string($valor)) {
            $valor = trim($valor);
        }

        if (! is_int($valor) && ! is_float($valor) && ! (is_string($valor) && is_numeric($valor))) {
            return null;
        }

        $segundos = (float) $valor;

        if (! is_finite($segundos) || $segundos <= 0) {
            return null;
        }

        return min($segundos, (float) self::PRAZO_MAXIMO);
    }
}
