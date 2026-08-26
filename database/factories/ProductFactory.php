<?php

namespace Database\Factories;

use App\Enums\ItemType;
use App\Models\Expositor;
use App\Models\Product;
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
