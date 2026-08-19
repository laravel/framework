<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class PivotTestProject extends Model
{
    public $table = 'projects';

    public function collaborators()
    {
        return $this->belongsToMany(
            PivotTestUser::class, 'collaborators', 'project_id', 'user_id'
        )->withPivot('permissions')
            ->using(PivotTestCollaborator::class);
    }

    public function contributors()
    {
        return $this->belongsToMany(PivotTestUser::class, 'contributors', 'project_id', 'user_id')
            ->withPivot('id', 'permissions')
            ->using(PivotTestContributor::class);
    }
}
