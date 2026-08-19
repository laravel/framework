<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class WherePivotClosureProject extends Model
{
    protected $table = 'projects';
    protected $guarded = [];
    public $timestamps = false;

    public function subscribers(): BelongsToMany
    {
        return $this->belongsToMany(WherePivotClosureUser::class, 'project_user', 'project_id', 'user_id')
            ->using(WherePivotClosureSubscription::class)
            ->withPivot(['role', 'muted']);
    }
}
