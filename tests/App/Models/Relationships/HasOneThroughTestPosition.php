<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class HasOneThroughTestPosition extends Model
{
    protected $table = 'positions';
    protected $guarded = [];

    public function contract()
    {
        return $this->hasOneThrough(HasOneThroughTestContract::class, HasOneThroughTestUser::class, 'position_id', 'user_id');
    }

    public function user()
    {
        return $this->hasOne(HasOneThroughTestUser::class, 'position_id');
    }
}
