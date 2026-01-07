<?php

namespace Database\Factories;

use App\Models\LingerieProduct;
use App\Models\LingerieProductImage;
use Illuminate\Database\Eloquent\Factories\Factory;

class LingerieProductImageFactory extends Factory
{
    protected $model = LingerieProductImage::class;

    public function definition(): array
    {
        return [
            'lingerie_product_id' => LingerieProduct::factory(),
            'image_url' => fake()->imageUrl(),
        ];
    }
}