<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class HasManyThroughDefaultTestUser extends Model
{
    protected $table = 'users_default';
    protected $guarded = [];

    public function posts()
    {
        return $this->hasMany(HasManyThroughDefaultTestPost::class);
    }
}
