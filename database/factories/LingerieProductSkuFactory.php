<?php

namespace Database\Factories;

use App\Models\LingerieProduct;
use App\Models\LingerieProductColor;
use App\Models\LingerieProductSize;
use App\Models\LingerieProductSku;
use Illuminate\Database\Eloquent\Factories\Factory;

class LingerieProductSkuFactory extends Factory
{
    protected $model = LingerieProductSku::class;

    public function definition(): array
    {
        return [
            'lingerie_product_id' => LingerieProduct::factory(),
            'size_id' => LingerieProductSize::factory(),
            'color_id' => LingerieProductColor::factory(),
            'price' => fake()->randomFloat(2, 10, 999),
            'stock_quantity' => fake()->numberBetween(0, 100),
        ];
    }
}