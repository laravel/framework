<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Relations\Pivot;

class DatabaseEloquentPivotTestDateStub extends Pivot
{
    public function getDates()
    {
        return [];
    }
}
