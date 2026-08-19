<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class EloquentStrictLoadingTestModel1WithLocalPreventsLazyLoading extends Model
{
    public $table = 'test_model1';
    public $timestamps = false;
    public $preventsLazyLoading = true;
    protected $guarded = [];

    public function modelTwos()
    {
        return $this->hasMany(EloquentStrictLoadingTestModel2::class, 'model_1_id');
    }
}
