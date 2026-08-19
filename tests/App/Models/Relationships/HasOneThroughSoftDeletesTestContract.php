<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class HasOneThroughSoftDeletesTestContract extends Model
{
    protected $table = 'contracts';
    protected $guarded = [];

    public function owner()
    {
        return $this->belongsTo(HasOneThroughSoftDeletesTestUser::class, 'user_id');
    }
}
