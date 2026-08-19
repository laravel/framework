<?php

namespace Illuminate\Tests\App\Models\Scopes;

class AsPivotPost extends SoftDeletingGlobalScopePost
{
    public function children()
    {
        return $this
            ->belongsToMany(static::class, (new AsPivotPostPivot)->getTable(), 'foreign_id', 'related_id')
            ->using(AsPivotPostPivot::class);
    }
}
