<?php

namespace App\Actions\Catalog;

use App\Exceptions\SemAutoridadeCanonica;
use App\Models\Expositor;
use App\Models\Product;
use App\Models\ProductOffer;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
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
 *
 * ## O que a CAT-DOM-02C mudou
 *
 * Duas coisas, e as duas vinham da CAT-DOM-02B.
 *
 * **O espelho legado acabou.** Até aqui os **doze** campos comerciais eram
 * gravados nos dois lados — `product_offers` como fonte de verdade e `products`
 * como cópia — para que nenhuma coluna do banco guardasse preço diferente do
 * que a oferta cobrava. As colunas continuam lá, fisicamente; o que parou foi a
 * escrita. Com N ofertas por produto, um espelho de coluna única não teria o
 * que refletir.
 *
 * `is_active` **não** é um deles. `CAMPOS_DA_OFERTA` o inclui porque
 * `product_offers.is_active` de fato vem do formulário do lojista; o que deixou
 * de acontecer é a cópia para `products.is_active`, que é validade canônica do
 * item e pertence à curadoria (D-CAT-10). Ele permanece em `products` como
 * coluna legítima, e não entra na remoção da CAT-DOM-02H.
 *
 * **Alterar a identidade do item passou a exigir autoridade.** Não basta ter
 * uma oferta sobre ele: é preciso curadoria ou delegação declarada (D-CAT-09).
 * A verificação vive na `ProductPolicy`, e aqui só se pergunta por ela.
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
     * Os doze espelhos comerciais legados de `products` — a dívida D-1.
     *
     * É `CAMPOS_DA_OFERTA` **menos `is_active`**, e a diferença é o ponto todo:
     * `is_active` existe nas duas tabelas com significados distintos —
     * disponibilidade comercial na oferta, validade canônica no produto
     * (D-CAT-10) —, então não é espelho de nada e não entra na remoção da
     * CAT-DOM-02H. Os outros doze não têm contrapartida canônica: são cópia
     * pura, e `products` não deve recebê-los nem em runtime, nem em fixture,
     * nem em seed.
     *
     * Nomeada aqui, e não repetida em cada lugar, porque quem precisa da lista
     * — a `ProductFactory` e o trait de seed — precisa exatamente da mesma.
     */
    public const ESPELHOS_COMERCIAIS_LEGADOS = [
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
        'is_featured',
        'sort_order',
    ];

    /**
     * @param  array<string, mixed>  $data  Campos já validados pela superfície.
     * @param  ProductOffer|null  $offer  Oferta a atualizar; nula cria item novo.
     * @param  User|null  $ator  Quem está salvando; usado só para autoridade canônica.
     *
     * @throws SemAutoridadeCanonica quando o ator tenta mudar a identidade do item sem poder
     */
    public function __invoke(
        array $data,
        Expositor $expositor,
        ?ProductOffer $offer = null,
        ?User $ator = null,
    ): ProductOffer {
        $ator ??= auth()->user();

        return DB::transaction(function () use ($data, $expositor, $offer, $ator) {
            $dadosDaOferta = Arr::only($data, self::CAMPOS_DA_OFERTA);

            if ($offer === null) {
                $product = Product::create($this->dadosDoProduto($data) + [
                    // Proveniência, não propriedade: registra quem trouxe o
                    // item para o catálogo. Nenhuma autorização olha para cá.
                    'expositor_id' => $expositor->id,
                ]);

                // Quem traz um item novo ao catálogo recebe, no mesmo ato, a
                // delegação para continuar editando o que ele é. É concessão
                // declarada — não decorre de ser o único ofertante, e some se a
                // curadoria a revogar (D-CAT-09).
                $product->delegarCanonicoPara($expositor->id);

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
            $offer->product->update($this->dadosDoProduto($data, $offer->product, $ator));
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
     * O que de fato vai para `products`.
     *
     * ## Fim do espelho (CAT-DOM-02C)
     *
     * Nada de `CAMPOS_DA_OFERTA` entra mais aqui. Doze desses campos são
     * espelhos comerciais legados — preço, estoque, dimensões, destaque, ordem
     * e condições de serviço —, gravados em cópia enquanto a cardinalidade real
     * era 1:1. As colunas continuam existindo, e removê-las é a CAT-DOM-02H;
     * o que parou foi a escrita.
     *
     * O décimo terceiro é `is_active`, e ele é caso à parte: em
     * `product_offers` é disponibilidade comercial e continua vindo do lojista;
     * em `products` é **validade canônica**, pertence à curadoria (D-CAT-10) e
     * por isso também não é escrito por aqui — mas como campo legítimo do
     * produto, não como espelho a remover.
     *
     * ## Autoridade sobre a identidade
     *
     * Na criação não há o que autorizar: o item está nascendo, e a delegação é
     * concedida no mesmo ato. Na edição, mexer em nome, descrições, eixo,
     * categoria ou natureza digital exige curadoria ou delegação válida.
     *
     * A verificação é sobre **mudança**, não sobre presença. Os dois
     * formulários reenviam o item inteiro a cada salvamento, então exigir
     * autoridade por o campo estar no payload impediria o lojista sem delegação
     * de corrigir o próprio preço — recusa que puniria o que ele pode fazer por
     * causa do que ele não mudou.
     *
     * `slug` sai do update de propósito: é derivado do nome pela plataforma e
     * não está entre os campos que a delegação alcança (D-CAT-09 §4.1). Na
     * criação ele continua sendo definido, e a API já se comportava assim —
     * `ProdutoController::buildData()` sempre preservou o slug existente. O que
     * muda é o painel Livewire passar a fazer o mesmo, encerrando uma
     * divergência entre os dois canais.
     *
     * `images` e `image_path` seguem sendo gravados como antes, sem exigir
     * autoridade: o desdobramento em imagem canônica e imagem da oferta é a
     * CAT-DOM-02D, e antecipá-lo aqui trocaria uma dívida conhecida por uma
     * meia-implementação.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     *
     * @throws SemAutoridadeCanonica
     */
    private function dadosDoProduto(array $data, ?Product $product = null, ?User $ator = null): array
    {
        $campos = Arr::only($data, self::CAMPOS_DO_PRODUTO);

        if ($product === null) {
            return $campos;
        }

        unset($campos['slug']);

        $mudancas = $this->mudancasCanonicas($product, $campos);

        if ($mudancas !== [] && ! $this->podeEditarCanonico($ator, $product)) {
            throw new SemAutoridadeCanonica($mudancas);
        }

        return $campos;
    }

    /**
     * Quais campos canônicos o payload realmente altera.
     *
     * A comparação é contra os atributos crus do banco, e não contra os do
     * model: `item_type` chega como string e sai do model como enum,
     * `is_digital` chega como bool e mora como `0`/`1`. Comparar as duas formas
     * direto acusaria mudança em todo salvamento, e a recusa passaria a
     * depender de casting em vez de intenção.
     *
     * @param  array<string, mixed>  $campos
     * @return array<int, string>
     */
    private function mudancasCanonicas(Product $product, array $campos): array
    {
        $atuais = $product->getAttributes();

        return array_values(array_filter(
            array_keys(Arr::only($campos, Product::CAMPOS_CANONICOS)),
            fn (string $campo) => ! $this->mesmoValor($atuais[$campo] ?? null, $campos[$campo]),
        ));
    }

    private function mesmoValor(mixed $atual, mixed $novo): bool
    {
        if ($atual === null || $novo === null) {
            return $atual === null && $novo === null;
        }

        return $this->normalizar($atual) === $this->normalizar($novo);
    }

    private function normalizar(mixed $valor): string
    {
        if (is_bool($valor)) {
            return (string) (int) $valor;
        }

        return (string) ($valor instanceof \BackedEnum ? $valor->value : $valor);
    }

    /**
     * A pergunta da D-CAT-09, feita em um lugar só.
     *
     * Delega à `ProductPolicy` — que não olha para a quantidade de ofertas nem
     * para `products.expositor_id` — em vez de repetir a regra aqui. Sem ator
     * não há autoridade: um caminho que salve sem usuário autenticado não herda
     * a permissão de ninguém.
     */
    private function podeEditarCanonico(?User $ator, Product $product): bool
    {
        return $ator !== null && Gate::forUser($ator)->allows('updateCanonical', $product);
    }
}
