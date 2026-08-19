<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class OtherCommentable extends Model
{
    public $timestamps = false;

    public function comments()
    {
        return $this->morphMany(ThroughComment::class, 'commentable');
    }
}
