<?php

namespace Illuminate\Tests\Database\Fixtures;

use Illuminate\Database\Eloquent\Model as Eloquent;

class EloquentTestUser extends Eloquent
{
    protected $table = 'users';
    protected $dates = ['birthday'];
    protected $guarded = [];

    public function friends()
    {
        return $this->belongsToMany(self::class, 'friends', 'user_id', 'friend_id');
    }

    public function friendsOne()
    {
        return $this->belongsToMany(self::class, 'friends', 'user_id', 'friend_id')->wherePivot('user_id', 1);
    }

    public function friendsTwo()
    {
        return $this->belongsToMany(self::class, 'friends', 'user_id', 'friend_id')->wherePivot('user_id', 2);
    }

    public function posts()
    {
        return $this->hasMany(EloquentTestPost::class, 'user_id');
    }

    public function post()
    {
        return $this->hasOne(EloquentTestPost::class, 'user_id');
    }

    public function photos()
    {
        return $this->morphMany(EloquentTestPhoto::class, 'imageable');
    }

    public function postWithPhotos()
    {
        return $this->post()->join('photo', function ($join) {
            $join->on('photo.imageable_id', 'post.id');
            $join->where('photo.imageable_type', 'EloquentTestPost');
        });
    }
}
