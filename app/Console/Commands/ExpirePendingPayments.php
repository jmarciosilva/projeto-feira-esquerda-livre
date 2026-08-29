<?php

namespace App\Console\Commands;

use App\Actions\Orders\ExpireOrder;
use App\Enums\OrderStatus;
use App\Exceptions\TransicaoDePedidoInvalida;
use App\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Throwable;

/**
 * Varre os pedidos pendentes cujo prazo venceu e devolve o estoque.
 *
 * "Prazo" tem duas origens, e o comando cobre as duas: o vencimento que o
 * gateway informou para a intenção de pagamento, e — quando nenhuma intenção
 * chegou a existir — a janela interna de checkout. O nome do comando continua
 * `orders:expire-payments` porque é o que a implantação já agenda; renomear
 * custaria mais do que a precisão ganha.
 *
 * ## Por que o comando não escreve status
 *
 * Ele decide *quais* pedidos olhar; `ExpireOrder` decide *se* cada um pode
 * expirar, sob lock. Essa divisão é o que torna seguras duas execuções
 * simultâneas: as duas podem selecionar o mesmo pedido, mas só a primeira a
 * pegar o lock encontra um pedido pendente — a segunda relê `Expirado` e não
 * faz nada.
 *
 * ## Por que uma transação por pedido
 *
 * Uma transação única para o lote inteiro seguraria locks de todas as ofertas
 * envolvidas até o fim da varredura, e uma falha no último pedido desfaria os
 * anteriores. Cada pedido responde por si.
 */
class ExpirePendingPayments extends Command
{
    protected $signature = 'orders:expire-payments {--limit=200 : Máximo de pedidos por execução}';

    protected $description = 'Expira pedidos pendentes com prazo vencido e devolve as reservas de estoque';

    public function handle(): int
    {
        $limite = max(1, (int) $this->option('limit'));

        $agora = now();

        // Duas consultas, e não um `OR` entre as duas colunas.
        //
        // O `OR` parecia natural, mas o `EXPLAIN` em MySQL mostrou o custo: com
        // a disjunção atravessando colunas diferentes, o otimizador usa só o
        // prefixo `status` de um dos índices e filtra o resto em `Using where`
        // — ou seja, varre todos os pedidos pendentes. Separadas, cada consulta
        // usa o seu índice inteiro, com a faixa temporal dentro dele.
        //
        // Comparação direta contra a coluna, sem função em cima dela: qualquer
        // `DATE()` ou `CAST()` inutilizaria os índices do mesmo jeito.
        // Sem `IS NOT NULL`: em SQL, `NULL <= agora` nao e verdadeiro, entao a
        // comparacao ja exclui quem nao tem prazo — e a condicao redundante
        // atrapalhava o otimizador. Ordenado pelo proprio prazo, o plano vai de
        // `ref` sobre 2.572 linhas com filesort para `range` sobre 858 com
        // `filtered=100` e sem sort. De quebra, e a ordem justa: quem venceu
        // ha mais tempo sai primeiro.
        $porPrazoDoGateway = Order::query()
            ->where('status', OrderStatus::AguardandoPagamento->value)
            ->where('payment_expires_at', '<=', $agora)
            ->orderBy('payment_expires_at')
            ->limit($limite)
            ->get();

        // A janela interna governa só quem não tem intenção nenhuma: o
        // `IS NULL` é o que impede alcançar um pedido cuja intenção externa
        // ainda está válida.
        // Aqui a forma atual ja e a melhor: `IS NULL` e igualdade para o indice,
        // entao a consulta usa as duas colunas (`key_len` cheio) com
        // `Using index condition` e sem filesort. Ordenar pelo prazo, ao
        // contrario de A, *acrescentaria* um sort — por isso as duas nao sao
        // simetricas.
        $porJanelaDeCheckout = $this->pendentes()
            ->whereNull('payment_expires_at')
            ->whereNotNull('checkout_expires_at')
            ->where('checkout_expires_at', '<=', $agora)
            ->limit($limite)
            ->get();

        $pedidos = $porPrazoDoGateway
            ->concat($porJanelaDeCheckout)
            ->unique('id')
            ->sortBy('id')
            ->take($limite);

        $expirados = 0;
        $ignorados = 0;

        foreach ($pedidos as $pedido) {
            try {
                $antes = $pedido->status;
                $depois = app(ExpireOrder::class)($pedido)->status;

                $antes !== $depois ? $expirados++ : $ignorados++;
            } catch (TransicaoDePedidoInvalida) {
                // Outra execução, ou o próprio pagamento, chegou primeiro.
                $ignorados++;
            } catch (Throwable $falha) {
                report($falha);
                $ignorados++;
            }
        }

        $this->info("Pedidos expirados: {$expirados}. Ignorados: {$ignorados}.");

        return self::SUCCESS;
    }

    /** @return Builder<Order> */
    private function pendentes(): Builder
    {
        return Order::query()
            ->where('status', OrderStatus::AguardandoPagamento->value)
            ->orderBy('id');
    }
}
