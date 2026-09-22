<?php

namespace Illuminate\Tests\Database\Fixtures\Models\PostLikes;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    public $timestamps = false;

    public function likes()
    {
        return $this->hasMany(Like::class);
    }
}
