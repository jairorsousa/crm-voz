<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->randomElement([
            'Cobrança ativa',
            'Régua de cobrança',
            'Recuperação de inadimplência',
            'Consultoria de cobrança',
        ]);

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.$this->faker->unique()->numberBetween(100, 999),
            'category' => $this->faker->randomElement(['Cobrança', 'Automação', 'Consultoria']),
            'description' => $this->faker->sentence(),
            'base_price' => $this->faker->randomFloat(2, 900, 15000),
            'is_active' => true,
            'sort_order' => $this->faker->numberBetween(1, 20),
        ];
    }
}
