<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class HasOneThroughTestContract extends Model
{
    protected $table = 'contracts';
    protected $guarded = [];

    public function owner()
    {
        return $this->belongsTo(HasOneThroughTestUser::class, 'user_id');
    }
}
