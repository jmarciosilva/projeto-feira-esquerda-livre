<?php

namespace App\CustomerIntelligence\Support;

use App\CustomerIntelligence\Enums\ConsentState;
use DateTimeInterface;
use Illuminate\Support\Carbon;

/**
 * Formato do cookie de preferencia de privacidade.
 *
 * O cookie e first-party, essencial por natureza (guarda a escolha da pessoa) e
 * separado dos cookies de analytics: ele existe justamente para dizer se
 * aqueles podem existir. Por isso nao herdou o prefixo `jmf_ci_`.
 *
 * O prazo de 12 meses e a propria validade do consentimento — quando o
 * navegador descarta o cookie, o estado volta a Unknown e a pergunta pode ser
 * feita de novo. Nao ha data de expiracao guardada dentro do valor: duas fontes
 * de verdade para a mesma coisa so criariam divergencia.
 *
 * `decided_at` fica no payload por outro motivo: mostrar a pessoa quando ela
 * decidiu, na tela de preferencias.
 */
final class ConsentCookie
{
    public static function name(): string
    {
        return (string) config('customer-intelligence-internal.consent.cookie.name', 'fel_privacy_consent');
    }

    public static function minutes(): int
    {
        return (int) config('customer-intelligence-internal.consent.cookie.minutes', 60 * 24 * 365);
    }

    public static function encode(ConsentState $state, DateTimeInterface $decidedAt): string
    {
        return (string) json_encode([
            'state' => $state->value,
            'decided_at' => Carbon::parse($decidedAt)->toIso8601String(),
        ]);
    }

    /**
     * Nunca lanca. Cookie ausente, truncado, adulterado ou de um formato antigo
     * devolve Unknown — o estado que nao autoriza coleta.
     *
     * @return array{0: ConsentState, 1: ?Carbon}
     */
    public static function decode(mixed $bruto): array
    {
        if (! is_string($bruto) || $bruto === '') {
            return [ConsentState::Unknown, null];
        }

        $payload = json_decode($bruto, true);

        if (! is_array($payload)) {
            return [ConsentState::Unknown, null];
        }

        $state = ConsentState::parse($payload['state'] ?? null);

        return [$state, self::parseDate($payload['decided_at'] ?? null)];
    }

    private static function parseDate(mixed $valor): ?Carbon
    {
        if (! is_string($valor) || $valor === '') {
            return null;
        }

        try {
            return Carbon::parse($valor);
        } catch (\Throwable) {
            return null;
        }
    }
}
