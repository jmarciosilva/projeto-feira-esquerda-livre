<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * CAT-DOM-02C — a autoridade canônica passa a ser um fato declarado.
 *
 * A CAT-DOM-02B congelou a D-CAT-09 com um invariante que o schema precisa
 * sustentar: **autoridade canônica não é cardinalidade**. A quantidade de
 * ofertas é estado comercial; quem pode reescrever a identidade de um item de
 * catálogo é estado de governança. Enquanto a segunda não tivesse coluna
 * própria, toda implementação seria tentada a deduzi-la da primeira — e foi
 * exatamente essa dedução que a decisão proibiu.
 *
 * ## Por que três colunas em `products`, e não uma tabela
 *
 * A tabela própria daria histórico de todas as delegações já concedidas. Foi
 * avaliada e recusada por dois motivos.
 *
 * O primeiro é o §18 da fase: **no máximo uma delegação ativa por produto**.
 * Numa coluna, isso é verdade por construção — não cabe um segundo valor. Numa
 * tabela, exigiria índice único parcial, que o MySQL 8.4 não tem, e cairia em
 * coluna gerada ou em regra de aplicação: mais peça para manter, e uma delas
 * fora do banco.
 *
 * O segundo é que o histórico completo ainda não tem consumidor. O que a fase
 * precisa saber é quem detém a delegação, se está ativa, quando nasceu e quando
 * foi revogada — e isso cabe nas três colunas. A entidade de contribuição da
 * fase futura trata de *propostas*, que são outro objeto; ela não herda desta.
 *
 * ## `canonical_delegate_expositor_id` não é `expositor_id`
 *
 * As duas colunas ficam lado a lado de propósito, e significam coisas
 * diferentes (D-CAT-11). `expositor_id` é **proveniência**: quem trouxe o item
 * ao catálogo, registro histórico que nenhuma autorização pode ler.
 * `canonical_delegate_expositor_id` é **autoridade**: quem a plataforma
 * autorizou a editar a identidade do item, agora, e de quem ela pode retirar
 * essa autorização a qualquer momento.
 *
 * Hoje as duas apontam para o mesmo expositor em todas as 75 linhas, porque o
 * backfill inicializa uma a partir da outra. Elas divergem no primeiro ato de
 * curadoria — e é por isso que precisam ser colunas distintas desde já.
 *
 * `nullOnDelete` é a semântica certa: sem expositor não há delegado. O produto
 * continua no catálogo, sob autoridade exclusiva da curadoria.
 *
 * ## O backfill
 *
 * Aditivo e determinístico. A medição do banco real antes desta migration:
 * 75 produtos, todos ativos, todos com expositor existente, 75 ofertas, 1:1,
 * zero produtos sem oferta e zero com mais de uma. Nesse estado, o único
 * delegado coerente com a história é o expositor de origem.
 *
 * `expositor_id` é usado aqui **como fonte de inicialização histórica, e nunca
 * como regra de runtime**: passada esta migration, a autoridade é lida
 * exclusivamente das colunas novas.
 *
 * Produto órfão — sem expositor — não recebe delegação: fica sob curadoria, que
 * é o estado correto para um item que ninguém trouxe e ninguém oferece.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('canonical_delegate_expositor_id')
                ->nullable()
                ->after('expositor_id')
                ->constrained('expositores')
                ->nullOnDelete();

            $table->timestamp('canonical_delegated_at')
                ->nullable()
                ->after('canonical_delegate_expositor_id');

            $table->timestamp('canonical_delegation_revoked_at')
                ->nullable()
                ->after('canonical_delegated_at');
        });

        // `whereNull` no destino torna a migration reexecutável sem sobrescrever
        // uma delegação que a curadoria já tenha movido.
        //
        // A cópia coluna a coluna precisa de `DB::raw`; a data, não. Em dois
        // passos com valor ligado em vez de um `COALESCE(created_at, NOW())`:
        // `NOW()` não existe em SQLite, e a suíte roda lá.
        DB::table('products')
            ->whereNotNull('expositor_id')
            ->whereNull('canonical_delegate_expositor_id')
            ->update([
                'canonical_delegate_expositor_id' => DB::raw('expositor_id'),
                'canonical_delegated_at' => DB::raw('created_at'),
            ]);

        // Item sem `created_at` — possível em base semeada — recebe o instante
        // da migration: a delegação existe desde que passou a ser declarada.
        DB::table('products')
            ->whereNotNull('canonical_delegate_expositor_id')
            ->whereNull('canonical_delegated_at')
            ->update(['canonical_delegated_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignId('canonical_delegate_expositor_id');
            $table->dropColumn(['canonical_delegated_at', 'canonical_delegation_revoked_at']);
        });
    }
};
