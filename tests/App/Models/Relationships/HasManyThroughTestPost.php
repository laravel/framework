<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class HasManyThroughTestPost extends Model
{
    protected $table = 'posts';
    protected $guarded = [];

    public function owner()
    {
        return $this->belongsTo(HasManyThroughTestUser::class, 'user_id');
    }
}
