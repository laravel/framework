<?php

namespace Illuminate\Tests\Integration\Http\Resources\JsonApi\Fixtures;

use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\Factories\UserFactory;

class TeamFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->company(),
            'user_id' => UserFactory::new(),
            'personal_team' => true,
        ];
    }

    #[\Override]
    public function modelName()
    {
        return Team::class;
    }
}
