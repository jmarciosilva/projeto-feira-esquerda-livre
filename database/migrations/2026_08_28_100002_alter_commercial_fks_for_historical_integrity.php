<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FIN-SEC-01B — o cadastro do vendedor é operacional; o pedido é histórico.
 *
 * Até aqui, `order_items`, `order_splits` e `order_shippings` apontavam para
 * `expositores` com ON DELETE CASCADE. A auditoria da FIN-SEC-01A reproduziu a
 * consequência: excluir um expositor apagava os itens do pedido, os splits, as
 * mensagens pós-venda, os envios e os eventos de rastreio — e deixava o `Order`
 * de pé, com `items_total` preenchido e nenhuma linha que o sustentasse.
 *
 * ## Por que não CASCADE
 *
 * CASCADE existe para composição: apagar o pai só faz sentido quando o filho
 * não significa nada sozinho. Não é o caso aqui. Um item de pedido é o registro
 * de uma venda que aconteceu, e ele continua verdadeiro depois que o vendedor
 * deixa a plataforma. O relacionamento com o vendedor é temporal; o fato
 * comercial, não.
 *
 * ## Por que SET NULL e não RESTRICT
 *
 * RESTRICT também protegeria o histórico, mas ao preço de tornar o expositor
 * indelével: qualquer loja que tivesse vendido uma vez jamais poderia ser
 * removida do cadastro. Isso contradiz a decisão do domínio — excluir um
 * expositor pode encerrar as ofertas dele, e deve poder ser feito. SET NULL
 * deixa a operação acontecer e preserva o fato, porque `expositor_name` guarda
 * quem vendeu (migration anterior) e `product_name`/`unit_price` já guardavam o
 * que foi vendido e por quanto.
 *
 * ## O que continua em CASCADE, de propósito
 *
 * `order_id` em items e splits, e `order_split_id` em shippings e messages:
 * essas são composições reais. Apagar o próprio pedido leva junto o que só
 * existe dentro dele — e nada disso é mais alcançado pela exclusão de um
 * expositor, que era o caminho perigoso.
 */
return new class extends Migration
{
    /** @var array<string, string> tabela => coluna */
    private const ALVOS = [
        'order_items' => 'expositor_id',
        'order_splits' => 'expositor_id',
        'order_shippings' => 'expositor_id',
    ];

    public function up(): void
    {
        foreach (self::ALVOS as $tabela => $coluna) {
            Schema::table($tabela, function (Blueprint $table) use ($coluna) {
                $table->dropForeign([$coluna]);
            });

            Schema::table($tabela, function (Blueprint $table) use ($coluna) {
                $table->unsignedBigInteger($coluna)->nullable()->change();
            });

            Schema::table($tabela, function (Blueprint $table) use ($coluna) {
                $table->foreign($coluna)->references('id')->on('expositores')->nullOnDelete();
            });
        }
    }

    /**
     * A volta restaura o CASCADE original. Só é aplicável enquanto não houver
     * linha com `expositor_id` nulo — depois disso, voltar significaria perder
     * exatamente o histórico que esta migration passou a proteger.
     */
    public function down(): void
    {
        foreach (self::ALVOS as $tabela => $coluna) {
            Schema::table($tabela, function (Blueprint $table) use ($coluna) {
                $table->dropForeign([$coluna]);
            });

            Schema::table($tabela, function (Blueprint $table) use ($coluna) {
                $table->unsignedBigInteger($coluna)->nullable(false)->change();
            });

            Schema::table($tabela, function (Blueprint $table) use ($coluna) {
                $table->foreign($coluna)->references('id')->on('expositores')->cascadeOnDelete();
            });
        }
    }
};
