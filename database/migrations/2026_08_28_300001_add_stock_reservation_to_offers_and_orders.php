<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FIN-SEC-01E — o estoque comprometido, e a prova de o que cada pedido fez.
 *
 * Até aqui `stock_quantity` era um número que o lojista digitava e que ninguém
 * consultava para decidir uma venda: dois clientes compravam a mesma última
 * unidade sem sequer precisar de concorrência.
 *
 * ## As duas colunas de quantidade
 *
 * `stock_quantity` continua sendo o **estoque físico** — o que existe na
 * prateleira, e é isso que o lojista edita no painel. `reserved_quantity` é o
 * que já está comprometido por pedidos ainda não pagos. O disponível é a
 * diferença:
 *
 *     available = stock_quantity - reserved_quantity
 *
 * Transformar `stock_quantity` em "disponível" seria mais curto, mas faria a
 * tela do lojista mentir: ele digita 10 porque tem 10, e com Pix pendente
 * precisa enxergar a diferença entre ter 10 e ter 10 com 3 já saindo.
 *
 * ## Por que marcas de tempo no pedido
 *
 * Sem elas, o status do pedido não distingue um `aguardando_pagamento` criado
 * hoje — que reservou — de um criado antes desta fase — que não reservou. E não
 * há como provar que uma reserva foi consumida ou liberada exatamente uma vez.
 * A ausência da marca é justamente o que identifica um pedido legado, sem
 * backfill nenhum e sem inferência por data de deploy.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_offers', function (Blueprint $table) {
            $table->unsignedInteger('reserved_quantity')
                ->default(0)
                ->after('stock_quantity');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('stock_reserved_at')->nullable()->after('paid_at');
            $table->timestamp('stock_consumed_at')->nullable()->after('stock_reserved_at');
            $table->timestamp('stock_released_at')->nullable()->after('stock_consumed_at');
        });
    }

    public function down(): void
    {
        Schema::table('product_offers', function (Blueprint $table) {
            $table->dropColumn('reserved_quantity');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['stock_reserved_at', 'stock_consumed_at', 'stock_released_at']);
        });
    }
};
