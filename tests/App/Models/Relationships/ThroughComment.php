<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class ThroughComment extends Model
{
    protected $table = 'comments';

    public $timestamps = false;

    public function commentable()
    {
        return $this->morphTo();
    }

    public function likes()
    {
        return $this->hasMany(ThroughLike::class, 'comment_id');
    }
}
