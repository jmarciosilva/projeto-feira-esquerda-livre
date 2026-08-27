<?php

namespace Database\Factories;

use App\Models\Expositor;
use App\Models\Product;
use App\Models\ProductOffer;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductOffer>
 */
class ProductOfferFactory extends Factory
{
    protected $model = ProductOffer::class;

    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'expositor_id' => Expositor::factory(),
            'price' => $this->faker->randomFloat(2, 10, 500),
            'has_stock' => true,
            'stock_quantity' => 10,
            'is_active' => true,
            'is_featured' => false,
            'sort_order' => 0,
        ];
    }

    public function doExpositor(Expositor $expositor): static
    {
        return $this->state(fn () => ['expositor_id' => $expositor->id]);
    }

    public function inativa(): static
    {
        return $this->state(fn () => ['is_active' => false]);
    }

    public function destacada(): static
    {
        return $this->state(fn () => ['is_featured' => true]);
    }

    /** Dimensões e peso reais, sem os quais nenhum provedor de frete cota. */
    public function comLogistica(): static
    {
        return $this->state(fn () => [
            'weight' => 0.5,
            'height' => 10,
            'width' => 15,
            'length' => 20,
        ]);
    }
}
