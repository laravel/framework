<?php

namespace Illuminate\Tests\App\Models\Casts;

use Illuminate\Database\Eloquent\Relations\Pivot;

class CustomPivotCastTestCollaborator extends Pivot
{
    public $timestamps = false;

    protected $attributes = [
        'permissions' => '["create", "update"]',
    ];

    protected $casts = [
        'permissions' => 'json',
    ];
}
