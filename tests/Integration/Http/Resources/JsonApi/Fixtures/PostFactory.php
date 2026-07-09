<?php

namespace Illuminate\Tests\Integration\Http\Resources\JsonApi\Fixtures;

use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\Factories\UserFactory;

class PostFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => UserFactory::new(),
            'title' => $this->faker->word(),
            'content' => $this->faker->words(10, true),
        ];
    }

    #[\Override]
    public function modelName()
    {
        return Post::class;
    }
}
