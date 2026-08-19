<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class HasOneThroughDefaultTestPosition extends Model
{
    protected $table = 'positions_default';
    protected $guarded = [];

    public function contract()
    {
        return $this->hasOneThrough(HasOneThroughDefaultTestContract::class, HasOneThroughDefaultTestUser::class);
    }

    public function user()
    {
        return $this->hasOne(HasOneThroughDefaultTestUser::class);
    }
}
