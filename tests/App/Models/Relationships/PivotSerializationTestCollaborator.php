<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Relations\Pivot;

class PivotSerializationTestCollaborator extends Pivot
{
    public $table = 'project_users';

    public $timestamps = false;
}
