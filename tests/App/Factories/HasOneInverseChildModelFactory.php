<?php

namespace Illuminate\Tests\App\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Tests\App\Models\Relationships\HasOneInverseChildModel;
use Illuminate\Tests\App\Models\Relationships\HasOneInverseParentModel;

class HasOneInverseChildModelFactory extends Factory
{
    protected $model = HasOneInverseChildModel::class;

    public function definition()
    {
        return [
            'parent_id' => HasOneInverseParentModel::factory(),
        ];
    }
}
