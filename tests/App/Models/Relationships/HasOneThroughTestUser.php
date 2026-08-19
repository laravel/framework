<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class HasOneThroughTestUser extends Model
{
    protected $table = 'users';
    protected $guarded = [];

    public function contract()
    {
        return $this->hasOne(HasOneThroughTestContract::class, 'user_id');
    }
}
