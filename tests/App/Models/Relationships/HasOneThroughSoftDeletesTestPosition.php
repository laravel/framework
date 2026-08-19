<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class HasOneThroughSoftDeletesTestPosition extends Model
{
    protected $table = 'positions';
    protected $guarded = [];

    public function contract()
    {
        return $this->hasOneThrough(HasOneThroughSoftDeletesTestContract::class, HasOneThroughTestUser::class, 'position_id', 'user_id');
    }

    public function user()
    {
        return $this->hasOne(HasOneThroughSoftDeletesTestUser::class, 'position_id');
    }
}
