<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class HasManyThroughCreateOrFirstTestPivotModel extends Model
{
    protected $table = 'pivot';
    protected $guarded = [];
}
