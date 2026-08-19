<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class NestedEagerLoadUser extends Model
{
    protected $table = 'users';

    public $timestamps = false;

    protected $guarded = [];

    public function posts()
    {
        return $this->hasMany(NestedEagerLoadPost::class, 'user_id');
    }

    public function avatar()
    {
        return $this->hasOne(Avatar::class, 'user_id');
    }
}
