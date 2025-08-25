<?php

namespace Illuminate\Tests\Integration\Database\EloquentMorphCountLazyEagerLoadingTest;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

class EloquentMorphCountLazyEagerLoadingTest extends DatabaseTestCase
{
    protected function afterRefreshingDatabase()
    {
        Schema::create('likes', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('post_id');
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->increments('id');
        });

        Schema::create('comments', function (Blueprint $table) {
            $table->increments('id');
            $table->string('commentable_type');
            $table->integer('commentable_id');
        });

        $post = Post::create();

        tap((new Like)->post()->associate($post))->save();
        tap((new Like)->post()->associate($post))->save();

        (new Comment)->commentable()->associate($post)->save();
    }

    public function testLazyEagerLoading()
    {
        $comment = Comment::first();

        $comment->loadMorphCount('commentable', [
            Post::class => ['likes'],
        ]);

        $this->assertTrue($comment->relationLoaded('commentable'));
        $this->assertEquals(2, $comment->commentable->likes_count);
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

class Like extends Model
{
    public $timestamps = false;

    public function post()
    {
        return $this->belongsTo(Post::class);
    }
}
