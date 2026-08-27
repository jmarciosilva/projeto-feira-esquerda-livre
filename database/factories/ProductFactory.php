<?php

namespace Database\Factories;

use App\Enums\ItemType;
use App\Models\Expositor;
use App\Models\Product;
use App\Models\ProductOffer;
use Illuminate\Database\Eloquent\Factories\Factory;
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
            'price' => $this->faker->randomFloat(2, 10, 500),
            'is_active' => true,
            'is_featured' => false,
            'is_digital' => false,
            'has_stock' => true,
            'stock_quantity' => 10,
            'sort_order' => 0,
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
     * Os valores comerciais espelham os campos legados de `products` enquanto a
     * dívida D-1 não for quitada, para que nenhuma coluna do banco guarde valor
     * diferente do que a oferta cobra.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (Product $product) {
            if ($product->expositor_id === null) {
                return;
            }

            ProductOffer::factory()->create([
                'product_id' => $product->id,
                'expositor_id' => $product->expositor_id,
                'price' => $product->price,
                'price_type' => $product->price_type,
                'modality' => $product->modality,
                'duration_min' => $product->duration_min,
                'weight' => $product->weight,
                'height' => $product->height,
                'width' => $product->width,
                'length' => $product->length,
                'has_stock' => $product->has_stock,
                'stock_quantity' => $product->stock_quantity,
                'is_active' => $product->is_active,
                'is_featured' => $product->is_featured,
                'sort_order' => $product->sort_order,
            ]);
        });
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

    public function servico(): static
    {
        return $this->state(fn () => [
            'item_type' => ItemType::Servico->value,
            'price_type' => 'fixo',
            'modality' => 'presencial',
            'has_stock' => false,
            'stock_quantity' => null,
        ]);
    }

    public function cuidado(): static
    {
        return $this->state(fn () => [
            'item_type' => ItemType::Cuidado->value,
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
