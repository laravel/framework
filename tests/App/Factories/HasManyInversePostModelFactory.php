<?php

namespace Illuminate\Tests\App\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Tests\App\Models\Relationships\HasManyInversePostModel;
use Illuminate\Tests\App\Models\Relationships\HasManyInverseUserModel;

class HasManyInversePostModelFactory extends Factory
{
    protected $model = HasManyInversePostModel::class;

    public function definition()
    {
        return [
            'user_id' => HasManyInverseUserModel::factory(),
        ];
    }
}
