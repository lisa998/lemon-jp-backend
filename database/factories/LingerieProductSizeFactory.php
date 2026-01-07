<?php

namespace Database\Factories;

use App\Models\LingerieProductSize;
use Illuminate\Database\Eloquent\Factories\Factory;

class LingerieProductSizeFactory extends Factory
{
    protected $model = LingerieProductSize::class;

    public function definition(): array
    {
        $cups = ['A', 'B', 'C', 'D', 'E', 'F'];
        $bands = ['65', '70', '75', '80', '85'];
        $bottoms = ['S', 'M', 'L'];

        $cup = fake()->randomElement($cups);
        $band = fake()->randomElement($bands);
        $bottom = fake()->randomElement($bottoms);

        return [
            'size' => "{$cup}{$band}/{$bottom}",
        ];
    }
}