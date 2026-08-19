<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class HasOneThroughSoftDeletesTestUser extends Model
{
    use SoftDeletes;

    protected $table = 'users';
    protected $guarded = [];

    public function contract()
    {
        return $this->hasOne(HasOneThroughSoftDeletesTestContract::class, 'user_id');
    }
}
