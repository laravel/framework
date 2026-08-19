<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class PivotEventsTestProject extends Model
{
    public $table = 'projects';

    public function collaborators()
    {
        return $this->belongsToMany(
            PivotEventsTestUser::class, 'project_users', 'project_id', 'user_id'
        )->using(PivotEventsTestCollaborator::class);
    }

    public function contributors()
    {
        return $this->belongsToMany(PivotEventsTestUser::class, 'project_users', 'project_id', 'user_id')
            ->using(PivotEventsTestCollaborator::class)
            ->wherePivot('role', 'contributor');
    }

    public function managers()
    {
        return $this->belongsToMany(PivotEventsTestUser::class, 'project_users', 'project_id', 'user_id')
            ->using(PivotEventsTestCollaborator::class)
            ->withPivotValue('role', 'manager');
    }

    public function equipments()
    {
        return $this->morphToMany(PivotEventsTestEquipment::class, 'equipmentable')->using(PivotEventsTestModelEquipment::class);
    }
}
