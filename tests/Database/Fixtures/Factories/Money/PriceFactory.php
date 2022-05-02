<?php

namespace Illuminate\Tests\Database\Fixtures\Factories\Money;

use Illuminate\Database\Eloquent\Factories\Factory;

class PriceFactory extends Factory
{
    public function definition()
    {
        return [
            'name' => $this->faker->name,
        ];
    }
}
