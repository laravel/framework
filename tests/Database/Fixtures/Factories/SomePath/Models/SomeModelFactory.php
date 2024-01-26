<?php

namespace Illuminate\Tests\Database\Fixtures\Factories\SomePath\Models;

use Illuminate\Database\Eloquent\Factories\Factory;

class SomeModelFactory extends Factory
{
    public function definition()
    {
        return [
            'name' => $this->faker->name(),
        ];
    }
}
