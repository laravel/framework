<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Relations\Pivot;

class PivotTestCollaborator extends Pivot
{
    public $table = 'collaborators';

    public $timestamps = false;

    protected $casts = [
        'permissions' => 'json',
    ];
}
