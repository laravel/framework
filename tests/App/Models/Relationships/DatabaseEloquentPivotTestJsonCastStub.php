<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Relations\Pivot;

class DatabaseEloquentPivotTestJsonCastStub extends Pivot
{
    protected $casts = [
        'foo' => 'json',
    ];
}
