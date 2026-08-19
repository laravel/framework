<?php

namespace Illuminate\Tests\App\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Tests\App\Models\Relationships\MorphManyInverseCommentModel;
use Illuminate\Tests\App\Models\Relationships\MorphManyInversePostModel;

class MorphManyInversePostModelFactory extends Factory
{
    protected $model = MorphManyInversePostModel::class;

    public function definition()
    {
        return [];
    }

    public function withComments(int $count = 3)
    {
        return $this->afterCreating(function (MorphManyInversePostModel $model) use ($count) {
            MorphManyInverseCommentModel::factory()->recycle($model)->count($count)->create();
        });
    }
}
