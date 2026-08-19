<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class HasManyThroughDefaultTestPost extends Model
{
    protected $table = 'posts_default';
    protected $guarded = [];

    public function owner()
    {
        return $this->belongsTo(HasManyThroughDefaultTestUser::class);
    }
}
