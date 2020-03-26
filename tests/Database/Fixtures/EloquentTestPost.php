<?php

namespace Illuminate\Tests\Database\Fixtures;

use Illuminate\Database\Eloquent\Model as Eloquent;

class EloquentTestPost extends Eloquent
{
    protected $table = 'posts';
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(EloquentTestUser::class, 'user_id');
    }

    public function photos()
    {
        return $this->morphMany(EloquentTestPhoto::class, 'imageable');
    }

    public function childPosts()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function parentPost()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }
}
