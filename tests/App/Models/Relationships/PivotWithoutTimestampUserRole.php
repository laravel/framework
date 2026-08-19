<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Relations\Pivot;

class PivotWithoutTimestampUserRole extends Pivot
{
    public $table = 'role_user';

    public function getUpdatedAtColumn()
    {
        return null;
    }
}
