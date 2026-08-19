<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class RelationAutoloadVideo extends Model
{
    protected $table = 'videos';

    public $timestamps = false;

    public function comments()
    {
        return $this->morphMany(RelationAutoloadComment::class, 'commentable');
    }

    public function likes()
    {
        return $this->morphMany(RelationAutoloadLike::class, 'likeable');
    }
}
