<?php

namespace Illuminate\Tests\App\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Tests\App\Models\Relationships\HasOneInverseParentModel;

class HasOneInverseParentModelFactory extends Factory
{
    protected $model = HasOneInverseParentModel::class;

    public function definition()
    {
        return [];
    }
}
