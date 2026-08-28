<?php

namespace App\Services\Shipping;

use App\Models\SiteSetting;
use Illuminate\Support\Collection;

/**
 * Cotação de frete do carrinho, loja a loja — o único lugar onde o preço do
 * transporte nasce.
 *
 * O checkout web e o da API precisam da mesma coisa: dado o carrinho e um CEP,
 * quais opções cada loja oferece e quanto custam. Antes, só o Livewire fazia
 * isso; a API recebia o valor pronto do cliente e acreditava. Concentrar aqui é
 * o que permite as duas superfícies responderem à mesma pergunta com a mesma
 * autoridade.
 *
 * A regra que este serviço existe para sustentar:
 *
 *     o cliente escolhe qual serviço; o servidor sabe quanto custa.
 */
final class CartShippingQuoter
{
    public function __construct(
        private readonly MelhorEnvioService $melhorEnvio,
        private readonly FrenetService $frenet,
    ) {}

    /**
     * Cotações por expositor, no formato que as duas superfícies consomem.
     *
     * Lojas sem item físico ficam de fora: não há o que despachar, e uma
     * entrada vazia só criaria a impressão de que falta escolher algo.
     *
     * @param  Collection<int|string, Collection<int, mixed>>  $itensAgrupados  carrinho agrupado por expositor
     * @return array<int|string, array<int, array<string, mixed>>>
     */
    public function porLoja(Collection $itensAgrupados, string $destinationZipcode): array
    {
        // Provedor escolhido manualmente no admin (padrão: Melhor Envio).
        $provedor = SiteSetting::instance()->frete_provedor === 'frenet'
            ? $this->frenet
            : $this->melhorEnvio;

        $cotacoes = [];

        foreach ($itensAgrupados as $expositorId => $storeItems) {
            $loja = $storeItems->first()?->expositor;

            if (! $loja) {
                continue;
            }

            $fisicos = $storeItems->filter(fn ($item) => ! ($item->product?->is_digital));

            if ($fisicos->isEmpty()) {
                continue;
            }

            $cotacoes[$expositorId] = collect(
                $provedor->quoteForStore($loja, $destinationZipcode, $fisicos)
            )->map->toArray()->all();
        }

        return $cotacoes;
    }

    /**
     * O preço que o servidor cotou para a opção que o cliente escolheu.
     *
     * Devolve `null` quando a escolha não corresponde a nenhuma opção
     * realmente oferecida àquela loja — serviço inexistente, serviço de outra
     * loja, cotação que veio com erro, ou escolha sem identificador. Quem
     * chama decide o que fazer com a recusa; o que este método nunca faz é
     * aceitar um preço vindo de fora.
     *
     * @param  array<int|string, array<int, array<string, mixed>>>  $cotacoes
     */
    public function precoDaEscolha(array $cotacoes, int|string $expositorId, ?string $serviceId): ?float
    {
        if ($serviceId === null) {
            return null;
        }

        $opcao = collect($cotacoes[$expositorId] ?? [])
            ->first(fn (array $cotada) => ($cotada['service_id'] ?? null) === $serviceId
                && empty($cotada['error_message']));

        return $opcao === null ? null : round((float) ($opcao['price'] ?? 0), 2);
    }
}
