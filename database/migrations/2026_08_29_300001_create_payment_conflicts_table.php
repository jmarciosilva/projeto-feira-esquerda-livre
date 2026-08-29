<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FIN-SEC-01F-D — onde fica a evidência de que o dinheiro chegou e o pedido não
 * pôde acompanhar.
 *
 * ## O buraco que esta tabela fecha (V-6)
 *
 * `ConfirmOrderPayment` roda inteira dentro de uma transação, e faz isso de
 * propósito: ou o pedido vira pago com estoque baixado e splits confirmados, ou
 * nada acontece. A consequência não intencional é que **a falha também some**.
 * Um pagamento aprovado no gateway sobre um pedido sem estoque disponível
 * disparava `EstoqueInsuficiente`, o rollback desfazia tudo, e o domínio ficava
 * sem uma única linha dizendo que aquele dinheiro existia.
 *
 * Por isso o conflito **não pode ser gravado dentro daquela transação**: ele é
 * exatamente o registro do que foi desfeito. Quem grava é
 * `RegisterPaymentConflict`, chamado depois do rollback.
 *
 * ## Por que RESTRICT e não CASCADE
 *
 * `order_items` e `order_splits` usam CASCADE em `order_id` porque são
 * composição — não significam nada fora do pedido. Um conflito financeiro é
 * outra coisa: ele registra que **dinheiro se moveu**, e essa é justamente a
 * informação que precisa sobreviver a qualquer limpeza de pedido. Apagar o
 * pedido e levar o conflito junto reabriria o buraco pelo outro lado.
 *
 * RESTRICT torna o pedido indelével enquanto houver conflito aberto ou
 * resolvido. É deliberado: um pedido com dinheiro pendente de reconciliação não
 * deve poder ser removido por conveniência operacional.
 *
 * ## Idempotência
 *
 * Webhook se repete. A chave única `(order_id, provider, type,
 * external_reference)` faz a segunda entrega do mesmo evento encontrar a linha
 * que já existe em vez de criar a décima cópia do mesmo problema.
 *
 * `external_reference` é NOT NULL por causa disso: em MySQL dois NULLs são
 * distintos numa chave única, e uma coluna nula aqui desligaria silenciosamente
 * a deduplicação justamente nos casos sem correlação — que são os que mais se
 * repetem. `RegisterPaymentConflict` normaliza a ausência para um sentinela
 * explícito.
 *
 * ## `context`
 *
 * Evidência mínima: o que se precisa para investigar, nunca o payload cru do
 * gateway, que carrega dado do pagador.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_conflicts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')->constrained('orders')->restrictOnDelete();

            $table->string('provider', 40);
            $table->string('type', 40);

            // Identidade do recurso do gateway envolvido — em geral o id do
            // pagamento. Nunca nulo: ver nota sobre idempotência acima.
            $table->string('external_reference', 100);

            // Valor envolvido no conflito, quando conhecido. Nulo significa
            // "o gateway não informou", nunca zero.
            $table->decimal('amount', 12, 2)->nullable();
            $table->string('currency', 3)->default('BRL');

            $table->json('context')->nullable();

            // Preenchido quando alguém reconciliou. Nulo é conflito aberto.
            $table->timestamp('resolved_at')->nullable();

            $table->timestamps();

            $table->unique(
                ['order_id', 'provider', 'type', 'external_reference'],
                'payment_conflicts_evento_unique',
            );

            // A fila de trabalho de quem reconcilia: conflitos abertos, mais
            // antigos primeiro.
            $table->index(['resolved_at', 'created_at'], 'payment_conflicts_resolved_at_created_at_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_conflicts');
    }
};
