<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class ParentModel extends Model
{
    public $table = 'parent';
    public $timestamps = false;
    protected $guarded = [];

    public function children()
    {
        return $this->hasMany(ChildModel::class, 'parent_id');
    }

    public function childrenDefaultConn2()
    {
        return $this->hasMany(ChildModelDefaultConn2::class, 'parent_id');
    }
}
