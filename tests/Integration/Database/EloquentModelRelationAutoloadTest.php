<?php

namespace Illuminate\Tests\Integration\Database\EloquentModelRelationAutoloadTest;

use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

class EloquentModelRelationAutoloadTest extends DatabaseTestCase
{
    protected function afterRefreshingDatabase()
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->increments('id');
        });

        Schema::create('videos', function (Blueprint $table) {
            $table->increments('id');
        });

        Schema::create('comments', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('parent_id')->nullable();
            $table->morphs('commentable');
        });

        Schema::create('likes', function (Blueprint $table) {
            $table->increments('id');
            $table->morphs('likeable');
        });
    }

    public function testRelationAutoload()
    {
        $post1 = Post::create();
        $comment1 = $post1->comments()->create(['parent_id' => null]);
        $comment2 = $post1->comments()->create(['parent_id' => $comment1->id]);
        $comment2->likes()->create();
        $comment2->likes()->create();

        $post2 = Post::create();
        $comment3 = $post2->comments()->create(['parent_id' => null]);
        $comment3->likes()->create();

        $posts = Post::get();

        DB::enableQueryLog();

        $likes = [];

        $posts->enableRelationAutoload();

        foreach ($posts as $post) {
            foreach ($post->comments as $comment) {
                $likes = array_merge($likes, $comment->likes->all());
            }
        }

        $this->assertCount(2, DB::getQueryLog());
        $this->assertCount(3, $likes);
        $this->assertTrue($posts[0]->comments[0]->relationLoaded('likes'));
    }

    public function testRelationAutoloadVariousNestedMorphRelations()
    {
        tap(Post::create(), function ($post) {
            $post->likes()->create();
            $post->comments()->create();
            tap($post->comments()->create(), function ($comment) {
                $comment->likes()->create();
                $comment->likes()->create();
            });
        });

        tap(Post::create(), function ($post) {
            $post->likes()->create();
            tap($post->comments()->create(), function ($comment) {
                $comment->likes()->create();
            });
        });

        tap(Video::create(), function ($video) {
            tap($video->comments()->create(), function ($comment) {
                $comment->likes()->create();
            });
        });

        tap(Video::create(), function ($video) {
            tap($video->comments()->create(), function ($comment) {
                $comment->likes()->create();
            });
        });

        $likes = Like::get();

        DB::enableQueryLog();

        $videos = [];
        $videoLike = null;

        $likes->enableRelationAutoload();

        foreach ($likes as $like) {
            $likeable = $like->likeable;

            if (($likeable instanceof Comment) && ($likeable->commentable instanceof Video)) {
                $videos[] = $likeable->commentable;
                $videoLike = $like;
            }
        }

        $this->assertCount(4, DB::getQueryLog());
        $this->assertCount(2, $videos);
        $this->assertTrue($videoLike->relationLoaded('likeable'));
        $this->assertTrue($videoLike->likeable->relationLoaded('commentable'));
    }
}

class Comment extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    public function parent()
    {
        return $this->belongsTo(self::class);
    }

    public function likes()
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    public function commentable()
    {
        return $this->morphTo();
    }
}

class Post extends Model
{
    public $timestamps = false;

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function likes()
    {
        return $this->morphMany(Like::class, 'likeable');
    }
}

class Video extends Model
{
    public $timestamps = false;

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function likes()
    {
        return $this->morphMany(Like::class, 'likeable');
    }
}

class Like extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    public function likeable()
    {
        return $this->morphTo();
    }
}
