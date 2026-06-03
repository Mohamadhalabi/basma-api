<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sku'            => fake()->unique()->bothify('SKU-####'),
            'title'          => fake()->words(3, true),
            'slug'           => fake()->unique()->slug(),
            'default_price'  => fake()->numberBetween(5000, 50000),
            'vat_rate'       => 15.00,
            'stock_quantity' => 100,
            'is_active'      => true,
        ];
    }
}
