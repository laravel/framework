<?php

namespace Illuminate\Tests\App\Models\Casts;

use Illuminate\Database\Eloquent\Model;

class CustomPivotCastTestProject extends Model
{
    public $table = 'projects';
    public $timestamps = false;

    public function collaborators()
    {
        return $this->belongsToMany(
            CustomPivotCastTestUser::class, 'project_users', 'project_id', 'user_id'
        )->using(CustomPivotCastTestCollaborator::class)->withPivot('permissions');
    }
}
