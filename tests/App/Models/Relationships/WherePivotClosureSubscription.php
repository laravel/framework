<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Relations\Pivot;

class WherePivotClosureSubscription extends Pivot
{
    protected $table = 'project_user';

    public function scopeActive($query)
    {
        return $query->where('muted', false);
    }

    public function scopeAdmins($query)
    {
        return $query->where('role', 'admin');
    }
}
