<?php

namespace App\Actions\Catalog;

use App\Models\Expositor;
use App\Models\Product;
use App\Models\ProductOffer;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * O único lugar onde um cadastro do lojista vira produto + oferta.
 *
 * Antes da CAT-DOM-01 a mesma regra de cadastro existia duas vezes —
 * `ProdutoForm::save()` no painel Livewire e `ProdutoController::buildData()`
 * na API mobile —, e a auditoria da 01A registrou que separar produto de oferta
 * **dobraria** essa duplicação: seriam quatro lugares decidindo qual campo é
 * identidade de catálogo e qual é condição de venda. Um deles divergiria.
 *
 * As duas superfícies continuam montando o mesmo array plano que já montavam.
 * A divisão acontece aqui, uma vez.
 */
final class SaveProductWithOffer
{
    /**
     * Identidade do item: o que ele é, independentemente de quem o vende.
     * Sobrevive à saída do expositor.
     */
    public const CAMPOS_DO_PRODUTO = [
        'item_type',
        'name',
        'slug',
        'short_description',
        'description',
        'category_id',
        'is_digital',
        'images',
        'image_path',
    ];

    /**
     * Condição de venda: quem oferece, por quanto e como. Morre com a oferta.
     *
     * `is_active` mora aqui porque o que o lojista liga e desliga é a **sua**
     * oferta — tirar o item do catálogo inteiro é decisão de curadoria, não de
     * quem vende.
     */
    public const CAMPOS_DA_OFERTA = [
        'price',
        'price_type',
        'modality',
        'duration_min',
        'weight',
        'height',
        'width',
        'length',
        'has_stock',
        'stock_quantity',
        'is_active',
        'is_featured',
        'sort_order',
    ];

    /**
     * @param  array<string, mixed>  $data  Campos já validados pela superfície.
     * @param  ProductOffer|null  $offer  Oferta a atualizar; nula cria item novo.
     */
    public function __invoke(array $data, Expositor $expositor, ?ProductOffer $offer = null): ProductOffer
    {
        return DB::transaction(function () use ($data, $expositor, $offer) {
            $dadosDaOferta = Arr::only($data, self::CAMPOS_DA_OFERTA);

            if ($offer === null) {
                $product = Product::create($this->dadosDoProduto($data) + [
                    // Proveniência, não propriedade: registra quem trouxe o
                    // item para o catálogo. Nenhuma autorização olha para cá.
                    'expositor_id' => $expositor->id,
                ]);

                $offer = ProductOffer::create($dadosDaOferta + [
                    'product_id' => $product->id,
                    'expositor_id' => $expositor->id,
                ]);

                // Devolve o produto recém-criado junto, em vez de deixar quem
                // chamou buscá-lo de novo: além da consulta poupada, é o que
                // preserva o `wasRecentlyCreated` de que a API depende para
                // responder 201 na criação.
                return $offer->setRelation('product', $product);
            }

            $this->recusarEstoqueAbaixoDoComprometido($offer, $dadosDaOferta);

            // `expositor_id` fica FORA dos dois updates, de propósito: o dono de
            // uma oferta existente nunca é recalculado a partir de quem está
            // salvando. É a mesma proteção da SEC-02, agora no lugar certo.
            $offer->product->update($this->dadosDoProduto($data));
            $offer->update($dadosDaOferta);

            return $offer->refresh();
        });
    }

    /**
     * O lojista não pode declarar menos estoque do que já vendeu.
     *
     * Baixar o físico para menos do que está comprometido criaria um disponível
     * negativo — unidades prometidas a pedidos que já existem e que ninguém
     * poderia atender. Aumentar continua livre, e não mexe nas reservas.
     *
     * A recusa é de validação, e não a `EstoqueInsuficiente` do checkout: quem
     * está do outro lado é o lojista corrigindo um campo, não um cliente vendo
     * a peça acabar. Ele precisa do erro embaixo do campo — 422 na API, mensagem
     * no formulário — e do número que explica por que aquele valor não cabe.
     *
     * @param  array<string, mixed>  $dadosDaOferta
     *
     * @throws ValidationException
     */
    private function recusarEstoqueAbaixoDoComprometido(ProductOffer $offer, array $dadosDaOferta): void
    {
        $novoFisico = $dadosDaOferta['stock_quantity'] ?? null;
        $comprometido = (int) $offer->reserved_quantity;

        if ($novoFisico === null || $comprometido === 0) {
            return;
        }

        if ((int) $novoFisico < $comprometido) {
            throw ValidationException::withMessages([
                'stock_quantity' => [sprintf(
                    '%d %s já estão comprometidas por pedidos em aberto; o estoque não pode ficar abaixo disso.',
                    $comprometido,
                    $comprometido === 1 ? 'unidade' : 'unidades',
                )],
            ]);
        }
    }

    /**
     * Enquanto a dívida D-1 não for quitada, `products` recebe também os campos
     * comerciais, em espelho. Não é fonte de verdade — nenhuma superfície os lê
     * de lá desde a CAT-DOM-01E —, mas manter o espelho evita que uma coluna do
     * banco guarde preço ou estoque diferente do que a oferta cobra.
     *
     * Quando as colunas legadas forem removidas, esta soma cai e sobra
     * `CAMPOS_DO_PRODUTO`.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function dadosDoProduto(array $data): array
    {
        return Arr::only($data, [...self::CAMPOS_DO_PRODUTO, ...self::CAMPOS_DA_OFERTA]);
    }
}
