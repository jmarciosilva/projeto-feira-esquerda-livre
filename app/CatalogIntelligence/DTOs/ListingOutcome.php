<?php

namespace App\CatalogIntelligence\DTOs;

use App\CatalogIntelligence\Enums\ListingOutcomeState;
use App\CatalogIntelligence\Enums\ProviderResponseViolation;
use InvalidArgumentException;

/**
 * O desfecho de uma sugestão — o DTO da D-CAT-06B-1.
 *
 * CAT-06G. Devolvido por `GenerateListingSuggestion::comContexto()` ao lado da
 * sugestão e do contexto. Fica **fora** de `ListingSuggestion`, cuja forma está
 * congelada desde a CAT-05D, e fora de `missing_information`: o que o item precisa
 * é informação de catálogo; se a inteligência caiu é informação de operação.
 *
 * ## O estado, e o motivo quando há
 *
 * `state` é o desfecho, exaustivo — ver `ListingOutcomeState`. `violations` só tem
 * conteúdo quando a resposta do provider foi recusada, e é o motivo que a
 * D-CAT-06D-3 mandou carregar: um booleano bastaria para descartar, e não para
 * registrar por quê. Em qualquer outro estado é lista vazia, e as duas portas de
 * construção não aceitam a combinação errada — combinação errada é bug de quem
 * monta o desfecho, e lança.
 */
final class ListingOutcome
{
    /** @param  array<int, ProviderResponseViolation>  $violations */
    private function __construct(
        public readonly ListingOutcomeState $state,
        public readonly array $violations,
    ) {}

    /** Qualquer desfecho que não carrega motivo — todos, menos a resposta inválida. */
    public static function de(ListingOutcomeState $state): self
    {
        if ($state === ListingOutcomeState::ProviderResponseInvalid) {
            throw new InvalidArgumentException('resposta inválida exige as violações: use ListingOutcome::respostaInvalida()');
        }

        return new self($state, []);
    }

    /** @param  array<int, ProviderResponseViolation>  $violations  As do `ProviderResponseValidator`, nunca vazias. */
    public static function respostaInvalida(array $violations): self
    {
        if ($violations === [] || ! array_is_list($violations)) {
            throw new InvalidArgumentException('resposta inválida sem violação não é resposta inválida');
        }

        foreach ($violations as $violacao) {
            if (! $violacao instanceof ProviderResponseViolation) {
                throw new InvalidArgumentException('o motivo de uma resposta inválida é uma ProviderResponseViolation');
            }
        }

        return new self(ListingOutcomeState::ProviderResponseInvalid, $violations);
    }

    /** @return array{state: string, violations: array<int, string>} */
    public function toArray(): array
    {
        return [
            'state' => $this->state->value,
            'violations' => array_map(fn (ProviderResponseViolation $v) => $v->value, $this->violations),
        ];
    }
}
