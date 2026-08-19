<?php

namespace Illuminate\Tests\App\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Tests\App\Models\Relationships\MorphOneInverseImageModel;
use Illuminate\Tests\App\Models\Relationships\MorphOneInversePostModel;

class MorphOneInverseImageModelFactory extends Factory
{
    protected $model = MorphOneInverseImageModel::class;

    public function definition()
    {
        return [
            'imageable_type' => MorphOneInversePostModel::class,
            'imageable_id' => MorphOneInversePostModel::factory(),
        ];
    }
}
