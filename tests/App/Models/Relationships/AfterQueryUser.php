<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class AfterQueryUser extends Model
{
    protected $table = 'users';
    protected $guarded = [];
    public $timestamps = false;

    public function teamMates()
    {
        return $this->hasManyThrough(self::class, AfterQueryTeam::class, 'owner_id', 'team_id');
    }

    public function posts()
    {
        return $this->belongsToMany(AfterQueryPost::class, 'users_posts', 'user_id', 'post_id')
            ->afterQuery(fn (Collection $posts) => $posts->keyBy(fn (AfterQueryPost $post) => $post->id))
            ->withTimestamps();
    }
}
