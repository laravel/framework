<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Relations\Pivot;

class PivotTestContributor extends Pivot
{
    public $table = 'contributors';

    public $timestamps = false;

    public $incrementing = true;

    protected $casts = [
        'permissions' => 'json',
    ];
}
