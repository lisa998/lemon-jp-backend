<?php

namespace Database\Factories;

use App\Models\LingerieProduct;
use App\Models\LingerieProductColor;
use App\Models\LingerieProductColorImage;
use Illuminate\Database\Eloquent\Factories\Factory;

class LingerieProductColorImageFactory extends Factory
{
    protected $model = LingerieProductColorImage::class;

    public function definition(): array
    {
        return [
            'product_id' => LingerieProduct::factory(),
            'color_id' => LingerieProductColor::factory(),
            'image_url' => fake()->imageUrl(),
        ];
    }
}