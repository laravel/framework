<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class PolymorphicIntegrationTestUser extends Model
{
    protected $table = 'users';
    protected $guarded = [];

    public function posts()
    {
        return $this->hasMany(PolymorphicIntegrationTestPost::class, 'user_id');
    }
}
