<?php

namespace Illuminate\Tests\App\Factories\JsonApi;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Tests\App\Models\JsonApi\ApiTeam;
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
        return ApiTeam::class;
    }
}
