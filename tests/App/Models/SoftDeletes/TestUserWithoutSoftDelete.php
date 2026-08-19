<?php

namespace Illuminate\Tests\App\Models\SoftDeletes;

use Illuminate\Database\Eloquent\Model;

class TestUserWithoutSoftDelete extends Model
{
    protected $table = 'users';
    protected $guarded = [];

    public function posts()
    {
        return $this->hasMany(SoftDeletesTestPost::class, 'user_id');
    }
}
