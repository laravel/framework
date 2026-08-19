<?php

namespace Illuminate\Tests\App\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Tests\App\Models\Relationships\HasManyInversePostModel;
use Illuminate\Tests\App\Models\Relationships\HasManyInverseUserModel;

class HasManyInverseUserModelFactory extends Factory
{
    protected $model = HasManyInverseUserModel::class;

    public function definition()
    {
        return [];
    }

    public function withPosts(int $count = 3)
    {
        return $this->afterCreating(function (HasManyInverseUserModel $model) use ($count) {
            HasManyInversePostModel::factory()->recycle($model)->count($count)->create();
        });
    }
}
