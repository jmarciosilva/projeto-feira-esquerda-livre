<?php

namespace App\Actions\Payments;

use App\Enums\PaymentConflictType;
use App\Models\Order;
use App\Models\PaymentConflict;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

/**
 * Grava a evidência durável de um conflito financeiro.
 *
 * ## Onde esta ação **não** pode ser chamada
 *
 * Dentro da transação que ela documenta. É a regra inteira do V-6: o conflito
 * de estoque insuficiente nasce de `ConfirmOrderPayment` falhando, e aquela
 * ação é uma transação só — gravar o conflito lá dentro faria o rollback levar
 * embora o registro do que foi desfeito, que é precisamente o problema que esta
 * tabela existe para resolver.
 *
 * O chamador correto é quem já recebeu a exceção **depois** do rollback, hoje
 * `MercadoPagoService::confirmar()`. A transação aberta aqui é nova, curta, e
 * não tem relação com a que falhou.
 *
 * ## Idempotência
 *
 * Webhook se repete — por reentrega do gateway, por retry de fila, por um
 * operador clicando duas vezes. A chave única
 * `(order_id, provider, type, external_reference)` garante uma linha por
 * evento; a segunda chamada encontra a primeira e devolve a mesma linha, sem
 * remarcar `created_at` nem reabrir um conflito já resolvido.
 *
 * `firstOrCreate` resolve o caso normal; o `try` em volta cobre a corrida real
 * entre duas entregas simultâneas, em que os dois `SELECT` não acham nada e o
 * banco recusa o segundo `INSERT`. A resposta certa aí é reler, não estourar.
 *
 * ## Por que a releitura é travada
 *
 * `lockForUpdate()` não está aí por concorrência — está por **visibilidade**.
 * Em MySQL, `REPEATABLE READ` fixa o snapshot da transação na primeira leitura,
 * e o `INSERT` do vencedor commitou depois disso: uma releitura comum consulta
 * aquele snapshot e não encontra a linha que acabou de derrubar o nosso
 * `INSERT`, transformando a corrida num `ModelNotFoundException`. Uma leitura
 * travada ignora o snapshot e lê a versão corrente.
 *
 * Reproduzido em MySQL real com cinco entregas simultâneas do mesmo evento; o
 * SQLite da suíte não expõe o caso, porque não tem MVCC.
 */
final class RegisterPaymentConflict
{
    /**
     * Sentinela para quando o gateway não deu identidade nenhuma ao evento.
     *
     * Existe porque `external_reference` é NOT NULL de propósito: em MySQL dois
     * NULLs são distintos numa chave única, e uma coluna nula desligaria a
     * deduplicação exatamente nos eventos sem correlação, que são os que mais
     * chegam repetidos.
     */
    public const SEM_CORRELACAO = 'sem_correlacao';

    /**
     * @param  array<string, mixed>  $context  evidência mínima para investigar —
     *                                         nunca o payload cru do gateway,
     *                                         que carrega dado do pagador
     */
    public function __invoke(
        Order $order,
        PaymentConflictType $tipo,
        string $provider,
        ?string $externalReference = null,
        ?float $amount = null,
        array $context = [],
        string $currency = 'BRL',
    ): PaymentConflict {
        $chave = [
            'order_id' => $order->getKey(),
            'provider' => $provider,
            'type' => $tipo->value,
            'external_reference' => $externalReference ?? self::SEM_CORRELACAO,
        ];

        $atributos = [
            'amount' => $amount,
            'currency' => $currency,
            'context' => $context,
        ];

        return DB::transaction(function () use ($chave, $atributos) {
            try {
                return PaymentConflict::firstOrCreate($chave, $atributos);
            } catch (UniqueConstraintViolationException) {
                // Duas entregas simultâneas do mesmo evento: a outra ganhou o
                // INSERT. A linha dela é a resposta certa — e só uma leitura
                // travada enxerga um commit posterior ao nosso snapshot.
                return PaymentConflict::where($chave)->lockForUpdate()->firstOrFail();
            }
        });
    }
}
