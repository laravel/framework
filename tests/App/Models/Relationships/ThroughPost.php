<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;

class ThroughPost extends Model
{
    protected $table = 'posts';

    public $timestamps = false;

    protected $guarded = [];

    protected $withCount = ['comments'];

    public function comments()
    {
        return $this->morphMany(ThroughComment::class, 'commentable');
    }

    public function commentLikes()
    {
        return $this->through($this->comments())->has('likes');
    }

    public function texts()
    {
        return $this->hasMany(ThroughText::class, 'post_id');
    }
}
