<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class UserX extends Model
{
    public $timestamps = false;
    protected $guarded = [];
    protected $table = 'users';

    public function posts()
    {
        return $this->hasMany(PostX::class, 'user_id');
    }
}
