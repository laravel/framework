<?php

namespace Illuminate\Tests\App\Models\SoftDeletes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class SoftDeletesTestUser extends Model
{
    use SoftDeletes;

    protected $table = 'users';
    protected $guarded = [];

    public function self_referencing()
    {
        return $this->hasMany(SoftDeletesTestUser::class, 'user_id')->onlyTrashed();
    }

    public function posts()
    {
        return $this->hasMany(SoftDeletesTestPost::class, 'user_id');
    }

    public function address()
    {
        return $this->hasOne(SoftDeletesTestAddress::class, 'user_id');
    }

    public function group()
    {
        return $this->belongsTo(SoftDeletesTestGroup::class, 'group_id');
    }
}
