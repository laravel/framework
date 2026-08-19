<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class PivotEventsTestEquipment extends Model
{
    public $table = 'equipments';

    public function getForeignKey()
    {
        return 'equipment_id';
    }

    public function projects()
    {
        return $this->morphedByMany(PivotEventsTestProject::class, 'equipmentable')->using(PivotEventsTestModelEquipment::class);
    }
}
