<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * FIN-SEC-01B — quem vendeu, no momento em que vendeu.
 *
 * `order_items` já guardava `product_name` e `unit_price` como snapshot, mas o
 * vendedor só existia como referência viva. Renomear uma loja reescrevia o
 * passado: um pedido de agosto passava a dizer que fora vendido pelo nome novo.
 *
 * O snapshot é deliberadamente mínimo — apenas o nome. CNPJ, endereço e dados
 * bancários pertencem ao cadastro operacional e não fazem falta para reconstruir
 * o fato comercial; copiá-los seria espalhar dado pessoal por uma tabela que
 * nunca mais é revisada.
 *
 * Nullable porque o backfill não inventa nome: pedido cujo expositor já não
 * existe fica sem snapshot, e é honesto que fique.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->string('expositor_name')->nullable()->after('expositor_id');
        });

        Schema::table('order_splits', function (Blueprint $table) {
            $table->string('expositor_name')->nullable()->after('expositor_id');
        });

        // Backfill a partir da referência viva, onde ela ainda existe. No banco
        // de desenvolvimento não há pedido nenhum hoje, mas a migration precisa
        // estar correta para uma base que já tenha histórico.
        //
        // Subconsulta correlacionada, e não `UPDATE ... JOIN`: a suíte roda em
        // SQLite e o join na cláusula de update só existe no MySQL.
        foreach (['order_items', 'order_splits'] as $tabela) {
            DB::table($tabela)
                ->whereNotNull('expositor_id')
                ->update([
                    'expositor_name' => DB::raw(
                        "(select name from expositores where expositores.id = {$tabela}.expositor_id)"
                    ),
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('expositor_name');
        });

        Schema::table('order_splits', function (Blueprint $table) {
            $table->dropColumn('expositor_name');
        });
    }
};
