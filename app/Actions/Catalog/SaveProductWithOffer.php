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

            $this->recusarMudancaQueOrfanaReserva($offer, $dadosDaOferta);

            // `expositor_id` fica FORA dos dois updates, de propósito: o dono de
            // uma oferta existente nunca é recalculado a partir de quem está
            // salvando. É a mesma proteção da SEC-02, agora no lugar certo.
            $offer->product->update($this->dadosDoProduto($data));
            $offer->update($dadosDaOferta);

            return $offer->refresh();
        });
    }

    /**
     * O lojista não pode desmanchar um compromisso que já assumiu.
     *
     * ## O que a FIN-SEC-01E já protegia
     *
     * Baixar o físico para menos do que está comprometido criaria um disponível
     * negativo — unidades prometidas a pedidos que já existem e que ninguém
     * poderia atender. Aumentar continua livre, e não mexe nas reservas.
     *
     * ## O que faltava, e a 01F-C fechou (FIN-SEC-01E.1)
     *
     * Aquela checagem só rodava quando **um número novo chegava**. Desligar o
     * controle de estoque não é baixar o número: é dizer que não há número. E
     * `OperaEstoqueDoPedido::controlaEstoque()` decide se a oferta participa das
     * operações de estoque olhando exatamente `has_stock` e `stock_quantity`.
     *
     * Com o controle desligado, a oferta saía do radar de `ConsumeOrderStock` e
     * de `ReleaseOrderStock`, e o `reserved_quantity` que já existia ficava
     * órfão — sem ninguém para devolvê-lo, e prendendo a oferta para sempre,
     * porque D-FIN-24 impede excluir oferta comprometida.
     *
     * Por isso, enquanto houver compromisso ativo, os três caminhos são
     * recusados: reduzir abaixo do comprometido, zerar a quantidade e desligar
     * `has_stock`. Sem compromisso, a semântica de ilimitado continua exatamente
     * como sempre foi — não é o controle de estoque que fica proibido, é
     * desligá-lo por cima de unidades já prometidas.
     *
     * A recusa é de validação, e não a `EstoqueInsuficiente` do checkout: quem
     * está do outro lado é o lojista corrigindo um campo, não um cliente vendo
     * a peça acabar. Ele precisa do erro embaixo do campo — 422 na API, mensagem
     * no formulário.
     *
     * @param  array<string, mixed>  $dadosDaOferta
     *
     * @throws ValidationException
     */
    private function recusarMudancaQueOrfanaReserva(ProductOffer $offer, array $dadosDaOferta): void
    {
        // O comprometido é relido sob lock: o valor que o formulário carregou
        // pode ter envelhecido enquanto o lojista digitava, e entre a leitura e
        // a escrita cabe um checkout inteiro. Trava apenas esta linha — não há
        // ciclo possível com a ordem `Order → ProductOffers` das demais
        // operações, porque aqui não existe pedido no caminho.
        $comprometido = (int) ProductOffer::query()
            ->whereKey($offer->getKey())
            ->lockForUpdate()
            ->value('reserved_quantity');

        if ($comprometido === 0) {
            return;
        }

        $desligouControle = array_key_exists('has_stock', $dadosDaOferta)
            && ! $dadosDaOferta['has_stock'];

        if ($desligouControle) {
            throw ValidationException::withMessages([
                'has_stock' => [$this->motivoDoCompromisso($comprometido, 'o controle de estoque não pode ser desligado agora')],
            ]);
        }

        // Ausente significa "não mexeu"; presente e nulo significa "apagou".
        if (array_key_exists('stock_quantity', $dadosDaOferta) && $dadosDaOferta['stock_quantity'] === null) {
            throw ValidationException::withMessages([
                'stock_quantity' => [$this->motivoDoCompromisso($comprometido, 'a quantidade não pode ficar em branco agora')],
            ]);
        }

        $novoFisico = $dadosDaOferta['stock_quantity'] ?? null;

        if ($novoFisico !== null && (int) $novoFisico < $comprometido) {
            throw ValidationException::withMessages([
                'stock_quantity' => [$this->motivoDoCompromisso($comprometido, 'o estoque não pode ficar abaixo disso')],
            ]);
        }
    }

    private function motivoDoCompromisso(int $comprometido, string $consequencia): string
    {
        return sprintf(
            '%d %s já %s comprometidas por pedidos em aberto; %s.',
            $comprometido,
            $comprometido === 1 ? 'unidade' : 'unidades',
            $comprometido === 1 ? 'está' : 'estão',
            $consequencia,
        );
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
