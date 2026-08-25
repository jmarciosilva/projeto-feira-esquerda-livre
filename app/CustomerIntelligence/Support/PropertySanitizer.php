<?php

namespace App\CustomerIntelligence\Support;

/**
 * Remove dados sensiveis das propriedades de um evento antes da gravacao.
 *
 * Herda a ideia do PayloadValidator do SDK externo, com uma diferenca
 * deliberada: o SDK lancava excecao, o modulo interno apenas redige o valor.
 * Rastreamento nunca deve derrubar um fluxo de compra por causa de uma chave
 * mal escolhida — mas tambem nao deve gravar o dado.
 */
class PropertySanitizer
{
    /**
     * Fragmentos de nome de chave considerados sensiveis. A comparacao e feita
     * sem diferenciar maiusculas e sem acento, sobre o nome da chave.
     *
     * @var list<string>
     */
    private const SENSITIVE = [
        'password', 'senha', 'secret', 'token', 'api_key', 'apikey',
        'authorization', 'credit_card', 'cartao', 'card_number', 'cvv',
        'cpf', 'cnpj', 'documento', 'rg',
    ];

    public const REDACTED = '[redigido]';

    /**
     * Profundidade maxima percorrida. Evita recursao infinita em estruturas
     * ciclicas e mantem o JSON gravado previsivel.
     */
    private const MAX_DEPTH = 5;

    /**
     * @param  array<string, mixed>  $properties
     * @return array<string, mixed>
     */
    public function sanitize(array $properties, int $depth = 0): array
    {
        if ($depth >= self::MAX_DEPTH) {
            return [];
        }

        $clean = [];

        foreach ($properties as $key => $value) {
            if (is_string($key) && $this->isSensitive($key)) {
                $clean[$key] = self::REDACTED;

                continue;
            }

            $clean[$key] = is_array($value)
                ? $this->sanitize($value, $depth + 1)
                : $value;
        }

        return $clean;
    }

    public function isSensitive(string $key): bool
    {
        $normalized = strtolower($key);

        foreach (self::SENSITIVE as $fragment) {
            if (str_contains($normalized, $fragment)) {
                return true;
            }
        }

        return false;
    }
}
