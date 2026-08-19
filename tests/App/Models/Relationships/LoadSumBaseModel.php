<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class LoadSumBaseModel extends Model
{
    protected $table = 'base_models';

    public $timestamps = false;

    protected $guarded = [];

    public function related1()
    {
        return $this->hasMany(LoadSumRelated1::class, 'base_model_id');
    }

    public function related2()
    {
        return $this->hasMany(LoadSumRelated2::class, 'base_model_id');
    }
}
