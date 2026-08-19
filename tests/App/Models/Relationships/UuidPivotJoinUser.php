<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class UuidPivotJoinUser extends Model
{
    public $table = 'users';
    public $timestamps = true;
    protected $guarded = [];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->setAttribute('uuid', Str::random());
        });
    }

    public function posts()
    {
        return $this->belongsToMany(TagsPost::class, 'users_posts', 'user_uuid', 'post_uuid', 'uuid', 'uuid')
            ->withPivot('is_draft')
            ->withTimestamps();
    }

    public function postsWithCustomPivot()
    {
        return $this->belongsToMany(TagsPost::class, 'users_posts', 'user_uuid', 'post_uuid', 'uuid', 'uuid')
            ->using(UserPostPivot::class)
            ->withPivot('is_draft')
            ->withTimestamps();
    }
}
