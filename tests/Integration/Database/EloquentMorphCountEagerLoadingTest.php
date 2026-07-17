<?php

namespace Illuminate\Tests\Integration\Database\EloquentMorphCountEagerLoadingTest;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

class EloquentMorphCountEagerLoadingTest extends DatabaseTestCase
{
    protected function afterRefreshingDatabase()
    {
        Schema::create('likes', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('post_id');
        });

        Schema::create('views', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('video_id');
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->increments('id');
        });

        Schema::create('videos', function (Blueprint $table) {
            $table->increments('id');
        });

        Schema::create('comments', function (Blueprint $table) {
            $table->increments('id');
            $table->string('commentable_type');
            $table->integer('commentable_id');
        });

        $post = Post::create();
        $video = Video::create();

        tap((new Like)->post()->associate($post))->save();
        tap((new Like)->post()->associate($post))->save();

        tap((new View)->video()->associate($video))->save();

        (new Comment)->commentable()->associate($post)->save();
        (new Comment)->commentable()->associate($video)->save();
    }

    public function testWithMorphCountLoading()
    {
        $comments = Comment::query()
            ->with(['commentable' => function (MorphTo $morphTo) {
                $morphTo->morphWithCount([Post::class => ['likes']]);
            }])
            ->get();

        $this->assertTrue($comments[0]->relationLoaded('commentable'));
        $this->assertEquals(2, $comments[0]->commentable->likes_count);
        $this->assertTrue($comments[1]->relationLoaded('commentable'));
        $this->assertNull($comments[1]->commentable->views_count);
    }

    public function testWithMorphCountLoadingWithSingleRelation()
    {
        $comments = Comment::query()
            ->with(['commentable' => function (MorphTo $morphTo) {
                $morphTo->morphWithCount([Post::class => 'likes']);
            }])
            ->get();

        $this->assertTrue($comments[0]->relationLoaded('commentable'));
        $this->assertEquals(2, $comments[0]->commentable->likes_count);
    }
}

class Comment extends Model
{
    public $timestamps = false;

    public function commentable()
    {
        return $this->morphTo();
    }
}

class Post extends Model
{
    public $timestamps = false;

    public function likes()
    {
        return $this->hasMany(Like::class);
    }
}

class Video extends Model
{
    public $timestamps = false;

    public function views()
    {
        return $this->hasMany(View::class);
    }
}

class Like extends Model
{
    public $timestamps = false;

    public function post()
    {
        return $this->belongsTo(Post::class);
    }
}

class View extends Model
{
    public $timestamps = false;

    public function video()
    {
        return $this->belongsTo(Video::class);
    }
}
