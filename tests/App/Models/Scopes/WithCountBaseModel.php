<?php

namespace Illuminate\Tests\App\Models\Scopes;

use Illuminate\Database\Eloquent\Model;

class WithCountBaseModel extends Model
{
    public $table = 'one';
    public $timestamps = false;
    protected $guarded = [];

    public function twos()
    {
        return $this->hasMany(LatestOrderGlobalScopeModel::class, 'one_id');
    }

    public function fours()
    {
        return $this->hasMany(RestrictedIdGlobalScopeModel::class, 'one_id');
    }

    public function allFours()
    {
        return $this->fours()->withoutGlobalScopes();
    }
}
