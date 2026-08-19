<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class HasManyThroughCreateOrFirstTestParentModel extends Model
{
    protected $table = 'parent';
    protected $guarded = [];

    public function children(): HasManyThrough
    {
        return $this->hasManyThrough(
            HasManyThroughCreateOrFirstTestChildModel::class,
            HasManyThroughCreateOrFirstTestPivotModel::class,
            'parent_id',
            'pivot_id',
        );
    }
}
