<?php

namespace Illuminate\Tests\Database\Fixtures\Models\PostLikes;

use Illuminate\Database\Eloquent\Model;

class Like extends Model
{
    public $timestamps = false;

    public function post()
    {
        return $this->belongsTo(Post::class);
    }
}
