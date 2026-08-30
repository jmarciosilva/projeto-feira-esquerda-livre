<?php

namespace Database\Factories;

use App\Actions\Catalog\SaveProductWithOffer;
use App\Enums\ItemType;
use App\Models\Expositor;
use App\Models\Product;
use App\Models\ProductOffer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * Antecipada na CAT-02.
 *
 * A CAT-01 registrou a ausência de uma factory de produto como dívida: 22
 * arquivos de teste montavam catálogo com `Product::create` à mão. A SEC-02
 * optou por não criá-la, porque três helpers locais bastavam. Aqui ela passa a
 * valer a pena: os testes do novo campo precisam de itens dos três eixos, com e
 * sem resumo, e repetir o array inteiro em cada caso esconderia o que cada
 * teste realmente prova.
 *
 * Os defaults são de catálogo, nada mais. Nenhum dado de inteligência —
 * embeddings, palavras-chave, sugestões e origem pertencem às estruturas
 * `catalog_*` das fases seguintes, nunca a `products`.
 *
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * Valores comerciais capturados antes de o produto ser gravado.
     *
     * Indexado por `spl_object_id` porque `create(3)` roda todos os
     * `afterMaking` e só depois todos os `afterCreating`: uma propriedade
     * simples seria sobrescrita pelo modelo seguinte.
     *
     * @var array<int, array<string, mixed>>
     */
    private array $comerciaisCapturados = [];

    /**
     * Só o que o produto **é**.
     *
     * Os defaults comerciais não moram aqui: eles são de `ProductOfferFactory`,
     * e é ela que decide preço e estoque de um item de teste.
     */
    public function definition(): array
    {
        $name = $this->faker->words(3, true);

        return [
            'expositor_id' => Expositor::factory(),
            'item_type' => ItemType::Produto->value,
            'name' => Str::ucfirst($name),
            'slug' => Str::slug($name).'-'.Str::random(6),
            // Nulo de propósito: o resumo curto é opcional no domínio, e o
            // caminho sem ele precisa ser o padrão dos testes.
            'short_description' => null,
            'description' => $this->faker->sentence(12),
            // `is_active` e `is_digital` são canônicos e ficam: o primeiro é a
            // validade do item no catálogo (D-CAT-10), o segundo é a natureza
            // dele. Nenhum dos dois é espelho comercial.
            'is_active' => true,
            'is_digital' => false,
        ];
    }

    /**
     * Todo produto de teste nasce com a oferta de quem o cadastrou.
     *
     * É o que acontece no domínio desde a CAT-DOM-01: o lojista cadastra um
     * item e, no mesmo ato, passa a oferecê-lo. Um produto sem oferta nenhuma
     * existe — é o item que ficou no catálogo depois que o expositor saiu —,
     * mas é o caso excepcional, não o padrão, e tem estado próprio abaixo.
     *
     * ## Por que os comerciais são interceptados no `afterMaking`
     *
     * Até a CAT-DOM-02C esta factory gravava preço e estoque em `products` e
     * depois **lia de lá** para montar a oferta. Isso ensinava a cada teste que
     * condição comercial é atributo de produto — exatamente o que a
     * CAT-DOM-02B decidiu que não é.
     *
     * Cerca de cinquenta chamadas espalhadas pela suíte escrevem
     * `Product::factory()->create(['price' => 120])`, e reescrever todas seria
     * uma fase inteira. Em vez disso, a chave comercial continua sendo aceita
     * como **açúcar de entrada** e é retirada do modelo **antes da gravação**:
     * `products` não recebe o valor, e a oferta recebe. Código novo deve usar
     * `comOferta()`, que diz na chamada onde o dado mora.
     */
    public function configure(): static
    {
        return $this
            ->afterMaking(function (Product $product) {
                // Os doze espelhos, e nada além. `is_active` fica no produto:
                // lá ele é validade canônica (D-CAT-10), e roteá-lo para a
                // oferta faria `->create(['is_active' => false])` significar
                // uma coisa na chamada e outra no banco.
                $comerciais = Arr::only(
                    $product->getAttributes(),
                    SaveProductWithOffer::ESPELHOS_COMERCIAIS_LEGADOS,
                );

                foreach (array_keys($comerciais) as $campo) {
                    unset($product->{$campo});
                }

                $this->comerciaisCapturados[spl_object_id($product)] = $comerciais;
            })
            ->afterCreating(function (Product $product) {
                $comerciais = Arr::pull($this->comerciaisCapturados, spl_object_id($product), []);

                if ($product->expositor_id === null) {
                    return;
                }

                // Espelha o que a `SaveProductWithOffer` faz na criação real:
                // quem traz o item ao catálogo recebe, no mesmo ato, a
                // delegação para editar o que ele é (CAT-DOM-02C). Sem isto,
                // todo produto de teste nasceria sem delegado e o cenário
                // comum — o lojista editando o próprio cadastro — deixaria de
                // ser reproduzível.
                $product->delegarCanonicoPara($product->expositor_id);

                ProductOffer::factory()->create($comerciais + [
                    'product_id' => $product->id,
                    'expositor_id' => $product->expositor_id,
                ]);
            });
    }

    /**
     * Condições comerciais ditas onde elas moram.
     *
     * Forma preferida em código novo: `Product::factory()->comOferta(['price' =>
     * 120])` deixa explícito que o preço é da oferta, e não do item.
     *
     * @param  array<string, mixed>  $comerciais
     */
    public function comOferta(array $comerciais): static
    {
        return $this->state(fn () => Arr::only($comerciais, SaveProductWithOffer::CAMPOS_DA_OFERTA));
    }

    /**
     * Item de catálogo que ninguém oferece — o produto que sobreviveu à saída
     * do expositor. Sem dono não há oferta a criar, e é essa a situação que a
     * CAT-DOM-01 existe para representar sem ambiguidade.
     */
    public function semOferta(): static
    {
        return $this->state(fn () => ['expositor_id' => null]);
    }

    public function comResumo(?string $resumo = null): static
    {
        return $this->state(fn () => [
            'short_description' => $resumo ?? $this->faker->sentence(8),
        ]);
    }

    /**
     * Serviço: `item_type` é do produto; forma de cobrança e modalidade são da
     * oferta, e chegam lá pelo roteamento do `configure()`.
     */
    public function servico(): static
    {
        return $this->state(fn () => [
            'item_type' => ItemType::Servico->value,
        ])->comOferta([
            'price_type' => 'fixo',
            'modality' => 'presencial',
            'has_stock' => false,
            'stock_quantity' => null,
        ]);
    }

    /** Mesma separação de `servico()`, com a cobrança por sessão. */
    public function cuidado(): static
    {
        return $this->state(fn () => [
            'item_type' => ItemType::Cuidado->value,
        ])->comOferta([
            'price_type' => 'por_sessao',
            'modality' => 'presencial',
            'has_stock' => false,
            'stock_quantity' => null,
        ]);
    }

    public function doExpositor(Expositor $expositor): static
    {
        return $this->state(fn () => ['expositor_id' => $expositor->id]);
    }
}
