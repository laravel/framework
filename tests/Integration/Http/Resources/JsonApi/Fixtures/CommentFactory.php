<?php

namespace Illuminate\Tests\Integration\Http\Resources\JsonApi\Fixtures;

use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\Factories\UserFactory;

class CommentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'post_id' => PostFactory::new(),
            'user_id' => UserFactory::new(),
            'content' => $this->faker->words(10, true),
        ];
    }
}
