<?php

namespace Database\Factories;

use App\Models\LingerieProduct;
use Illuminate\Database\Eloquent\Factories\Factory;

class LingerieProductFactory extends Factory
{
    protected $model = LingerieProduct::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'product_code' => fake()->unique()->regexify('[A-Z]{2}[0-9]{6}'),
            'description' => fake()->optional()->sentence(),
            'detail' => fake()->optional()->randomElement([
                ['material' => 'cotton', 'origin' => 'Japan'],
                ['material' => 'silk', 'origin' => 'China'],
                null,
            ]),
        ];
    }
}