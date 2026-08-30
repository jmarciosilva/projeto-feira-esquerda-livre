<?php

namespace App\Actions\Payments;

use App\Enums\PaymentConflictType;
use App\Models\Order;
use App\Models\PaymentConflict;
use Illuminate\Database\QueryException;
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
 * entre entregas simultâneas, em que os `SELECT` não acham nada e o banco
 * recusa o `INSERT` do segundo. A resposta certa aí é reler, não estourar.
 *
 * ## Por que a recuperação vive **fora** da transação (FIN-SEC-01G)
 *
 * O InnoDB tem mais de uma forma de recusar o perdedor, e elas não são
 * equivalentes:
 *
 * - **1062, chave duplicada** — a transação sobrevive, e uma releitura
 *   resolveria de dentro dela;
 * - **1213, deadlock** — o MySQL **desfaz a transação inteira** para quebrar o
 *   ciclo. Não há transação para reler de dentro;
 * - **1205, lock wait timeout** — a instrução falha e a transação fica em
 *   estado que não se deve presumir.
 *
 * A 01F-D tratava só a primeira, capturando `UniqueConstraintViolationException`
 * dentro da transação. Com oito entregas simultâneas do mesmo evento — provado
 * em `tests/Concurrency/prove.sh` —, uma delas chega como deadlock e escapava
 * como `QueryException` crua.
 *
 * Capturar `QueryException` do lado de fora cobre as três de uma vez: a
 * transação já terminou (commitada ou desfeita), e a releitura em autocommit
 * abre um snapshot novo, que enxerga o commit do vencedor. Sem
 * `lockForUpdate()`: fora de transação não há snapshot velho para furar.
 *
 * O rethrow importa tanto quanto o retorno. Se a linha **não** estiver lá, a
 * falha não era colisão — era falha de verdade, e engoli-la esconderia um
 * conflito financeiro que ninguém registrou.
 *
 * O SQLite da suíte não expõe nada disso, porque não tem MVCC nem deadlock de
 * linha. Por isso a prova mora em `tests/Concurrency/`.
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

        try {
            return DB::transaction(fn () => PaymentConflict::firstOrCreate($chave, $atributos));
        } catch (QueryException $colisao) {
            // Entregas simultâneas do mesmo evento: outra ganhou o INSERT. A
            // linha dela é a resposta certa.
            $registrado = PaymentConflict::where($chave)->first();

            if ($registrado !== null) {
                return $registrado;
            }

            // Não havia colisão nenhuma — o banco falhou de verdade.
            throw $colisao;
        }
    }
}
