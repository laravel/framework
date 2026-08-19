<?php

namespace Illuminate\Tests\App\Models\Relationships;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Tests\App\Models\Scopes\TagWithGlobalScope;

class TagsPost extends Model
{
    public $table = 'posts';
    public $timestamps = true;
    protected $guarded = [];
    protected $touches = ['touchingTags'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->setAttribute('uuid', Str::random());
        });
    }

    public function users()
    {
        return $this->belongsToMany(UuidPivotJoinUser::class, 'users_posts', 'post_uuid', 'user_uuid', 'uuid', 'uuid')
            ->withPivot('is_draft')
            ->withTimestamps();
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'posts_tags', 'post_id', 'tag_id')
            ->withPivot('flag')
            ->withTimestamps()
            ->wherePivot('flag', '<>', 'exclude');
    }

    public function tagsUnique()
    {
        return $this->belongsToMany(UniqueTag::class, 'posts_unique_tags', 'post_id', 'tag_id')
            ->withPivot('flag')
            ->withTimestamps()
            ->wherePivot('flag', '<>', 'exclude');
    }

    public function tagsWithExtraPivot()
    {
        return $this->belongsToMany(Tag::class, 'posts_tags', 'post_id', 'tag_id')
            ->withPivot('flag');
    }

    public function touchingTags()
    {
        return $this->belongsToMany(TouchingTag::class, 'posts_tags', 'post_id', 'tag_id')
            ->withTimestamps();
    }

    public function tagsWithCustomPivot()
    {
        return $this->belongsToMany(TagWithCustomPivot::class, 'posts_tags', 'post_id', 'tag_id')
            ->using(PostTagPivot::class)
            ->withTimestamps();
    }

    public function tagsWithCustomExtraPivot()
    {
        return $this->belongsToMany(TagWithCustomPivot::class, 'posts_tags', 'post_id', 'tag_id')
            ->using(PostTagPivot::class)
            ->withTimestamps()
            ->withPivot('flag');
    }

    public function tagsWithCustomPivotClass()
    {
        return $this->belongsToMany(TagWithCustomPivot::class, PostTagPivot::class, 'post_id', 'tag_id');
    }

    public function tagsWithCustomAccessor()
    {
        return $this->belongsToMany(TagWithCustomPivot::class, 'posts_tags', 'post_id', 'tag_id')
            ->using(PostTagPivot::class)
            ->as('tag');
    }

    public function tagsWithCustomRelatedKey()
    {
        return $this->belongsToMany(Tag::class, 'posts_tags', 'post_id', 'tag_name', 'id', 'name')
            ->withPivot('flag');
    }

    public function tagsWithGlobalScope()
    {
        return $this->belongsToMany(TagWithGlobalScope::class, 'posts_tags', 'post_id', 'tag_id');
    }
}
