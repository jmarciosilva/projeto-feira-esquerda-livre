<?php

namespace Database\Seeders\Concerns;

use App\Actions\Catalog\SaveProductWithOffer;
use App\Models\Product;
use App\Models\ProductOffer;
use Illuminate\Support\Arr;

/**
 * Semeia um item de catálogo com a oferta de quem o oferece.
 *
 * ## O que mudou na CAT-DOM-02C
 *
 * Antes, cada seeder gravava o array inteiro — canônico e comercial — em
 * `products`, e este trait **lia os campos comerciais de lá** para montar a
 * oferta. `products` funcionava como área de passagem de preço e estoque, o que
 * é exatamente o que a CAT-DOM-02B decidiu que ele não é.
 *
 * Agora a divisão acontece aqui, uma vez, do mesmo jeito que a
 * `SaveProductWithOffer` faz no cadastro real: os campos comerciais vão direto
 * para `product_offers`, e `products` recebe apenas o que o item **é**.
 *
 * Os seeders continuam idempotentes por `name` ou `slug` — trocá-los pela
 * action custaria essa idempotência sem ganho.
 */
trait SincronizaOfertaDoItem
{
    /**
     * @param  array<string, mixed>  $chave  Critério idempotente do seeder.
     * @param  array<string, mixed>  $dados  Array plano, canônico e comercial junto.
     */
    protected function semearItemComOferta(array $chave, array $dados, int $expositorId): ProductOffer
    {
        // Os doze espelhos saem de `products` e vão para a oferta. `is_active`
        // não é um deles: no produto é validade canônica e continua sendo
        // gravado lá; na oferta, o default do schema resolve.
        $comerciais = Arr::only($dados, SaveProductWithOffer::ESPELHOS_COMERCIAIS_LEGADOS);

        $product = Product::updateOrCreate(
            $chave,
            Arr::except($dados, SaveProductWithOffer::ESPELHOS_COMERCIAIS_LEGADOS),
        );

        // Quem semeia um item também é quem o traria ao catálogo em produção, e
        // lá esse ato concede a delegação canônica. Sem isto, o item semeado
        // nasceria sem delegado e o lojista de demonstração não conseguiria
        // editar o próprio cadastro no ambiente de desenvolvimento.
        if (! $product->temDelegacaoCanonicaAtiva()) {
            $product->delegarCanonicoPara($expositorId);
        }

        return ProductOffer::updateOrCreate(
            ['product_id' => $product->id, 'expositor_id' => $expositorId],
            $comerciais,
        )->setRelation('product', $product);
    }
}
