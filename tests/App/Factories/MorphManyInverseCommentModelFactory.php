<?php

namespace Illuminate\Tests\App\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Tests\App\Models\Relationships\MorphManyInverseCommentModel;
use Illuminate\Tests\App\Models\Relationships\MorphManyInversePostModel;

class MorphManyInverseCommentModelFactory extends Factory
{
    protected $model = MorphManyInverseCommentModel::class;

    public function definition()
    {
        return [
            'commentable_type' => MorphManyInversePostModel::class,
            'commentable_id' => MorphManyInversePostModel::factory(),
        ];
    }
}
