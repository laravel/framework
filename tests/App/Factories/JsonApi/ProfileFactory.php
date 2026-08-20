<?php

namespace Illuminate\Tests\App\Factories\JsonApi;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Tests\App\Models\JsonApi\Profile;
use Orchestra\Testbench\Factories\UserFactory;

class ProfileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => UserFactory::new(),
        ];
    }

    #[\Override]
    public function modelName()
    {
        return Profile::class;
    }
}
