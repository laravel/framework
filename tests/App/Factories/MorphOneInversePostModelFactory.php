<?php

namespace Illuminate\Tests\App\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Tests\App\Models\Relationships\MorphOneInversePostModel;

class MorphOneInversePostModelFactory extends Factory
{
    protected $model = MorphOneInversePostModel::class;

    public function definition()
    {
        return [];
    }
}
