<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Relations\Pivot;

class PivotTestSubscription extends Pivot
{
    public $table = 'subscriptions';

    public $timestamps = false;

    protected $attributes = [
        'status' => 'active',
    ];
}
