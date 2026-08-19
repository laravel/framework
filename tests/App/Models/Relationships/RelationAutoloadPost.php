<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class RelationAutoloadPost extends Model
{
    protected $table = 'posts';

    public $timestamps = false;

    public function shouldApplyStatus()
    {
        return false;
    }

    public function comments()
    {
        return $this->morphMany(RelationAutoloadComment::class, 'commentable');
    }

    public function commentsWithChaperone()
    {
        return $this->morphMany(RelationAutoloadComment::class, 'commentable')->chaperone();
    }

    public function likes()
    {
        return $this->morphMany(RelationAutoloadLike::class, 'likeable');
    }

    public function tags()
    {
        return $this->hasMany(RelationAutoloadTag::class, 'post_id');
    }
}
