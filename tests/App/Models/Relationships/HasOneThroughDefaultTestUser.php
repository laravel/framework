<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class HasOneThroughDefaultTestUser extends Model
{
    protected $table = 'users_default';
    protected $guarded = [];

    public function contract()
    {
        return $this->hasOne(HasOneThroughDefaultTestContract::class);
    }
}
