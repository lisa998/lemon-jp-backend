<?php

namespace Database\Factories;

use App\Models\LingerieProductColor;
use Illuminate\Database\Eloquent\Factories\Factory;

class LingerieProductColorFactory extends Factory
{
    protected $model = LingerieProductColor::class;

    public function definition(): array
    {
        return [
            'color' => fake()->unique()->safeColorName(),
        ];
    }
}