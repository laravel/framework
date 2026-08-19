<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class WhereHasUser extends Model
{
    protected $table = 'users';

    public $timestamps = false;

    public function posts()
    {
        return $this->hasMany(WhereHasPost::class, 'user_id');
    }
}
