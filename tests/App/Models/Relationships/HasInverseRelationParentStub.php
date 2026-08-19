<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class HasInverseRelationParentStub extends Model
{
    protected static $unguarded = true;
    protected $primaryKey = 'id';

    public function getForeignKey()
    {
        return 'parent_stub_id';
    }
}
