<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * FIN-SEC-01C — quanto de frete coube a cada loja.
 *
 * O cliente escolhe uma cotação **por loja** no checkout, mas até aqui o pedido
 * só guardava a soma (`orders.shipping_total`). O detalhe por vendedor virava
 * texto livre em `shipping_note` — "Loja 154: Correios PAC - R$ 25,00" —, onde
 * nenhum relatório consegue ler e onde a loja aparece por id, não por nome.
 *
 * Sem esse valor, o split não consegue responder quanto daquela venda foi
 * mercadoria e quanto foi transporte, que é exatamente a pergunta que qualquer
 * acerto de repasse vai fazer.
 *
 * ## Por que nullable, e não `default 0`
 *
 * Zero é uma afirmação: "não houve frete para esta loja". Só se pode afirmar
 * isso quando o pedido de fato não teve frete, ou quando o frete inteiro é de
 * uma única loja. Quando o valor chega apenas como total agregado de um pedido
 * com várias lojas — o que ainda acontece no checkout da API —, a divisão é
 * desconhecida, e `NULL` é a resposta honesta. Preencher com zero seria
 * inventar histórico.
 *
 * Pelo mesmo motivo não há backfill: não existe fonte confiável para dividir
 * retroativamente o frete de um pedido antigo, e o texto de `shipping_note` não
 * é dado, é frase.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_splits', function (Blueprint $table) {
            $table->decimal('shipping_amount', 10, 2)
                ->nullable()
                ->after('net_amount');
        });

        // Único caso deduzível com segurança: pedido sem frete nenhum. Aí zero
        // não é chute, é o fato.
        DB::table('order_splits')
            ->whereIn('order_id', DB::table('orders')->where('shipping_total', 0)->pluck('id'))
            ->update(['shipping_amount' => 0]);
    }

    /**
     * Tecnicamente reversivel, semanticamente destrutivo depois do primeiro uso.
     *
     * Assim que um pedido gravar aqui quanto de frete coube a cada loja, esse
     * numero deixa de existir em qualquer outro lugar: `orders.shipping_total`
     * guarda so a soma, e `shipping_note` e frase, nao dado. Derrubar a coluna
     * apaga o fato, e reaplicar a migration nao o traz de volta.
     *
     * Use apenas enquanto nenhum pedido com frete tiver sido criado sobre ela.
     */
    public function down(): void
    {
        Schema::table('order_splits', function (Blueprint $table) {
            $table->dropColumn('shipping_amount');
        });
    }
};
