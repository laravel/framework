<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class AfterQueryTeam extends Model
{
    protected $table = 'teams';
    protected $guarded = [];
    public $timestamps = false;

    public function members()
    {
        return $this->hasMany(AfterQueryUser::class, 'team_id');
    }
}
