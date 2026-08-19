<?php

namespace Illuminate\Tests\App\Models\Scopes;

class EloquentGlobalScopesWithRelationModel extends EloquentClosureGlobalScopesTestModel
{
    protected $table = 'table2';

    public function related()
    {
        return $this->hasMany(EloquentGlobalScopesTestModel::class, 'related_id')->where('foo', 'bar');
    }
}
