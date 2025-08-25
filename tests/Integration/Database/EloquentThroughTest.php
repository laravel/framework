<?php

namespace Illuminate\Tests\Integration\Database\EloquentThroughTest;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

class EloquentThroughTest extends DatabaseTestCase
{
    protected function afterRefreshingDatabase()
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->increments('id');
            $table->boolean('public');
        });

        Schema::create('other_commentables', function (Blueprint $table) {
            $table->increments('id');
        });

        Schema::create('comments', function (Blueprint $table) {
            $table->increments('id');
            $table->string('commentable_type');
            $table->integer('commentable_id');
        });

        Schema::create('likes', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('comment_id');
        });

        $post = tap(new Post(['public' => true]))->save();
        $comment = tap((new Comment)->commentable()->associate($post))->save();
        (new Like())->comment()->associate($comment)->save();
        (new Like())->comment()->associate($comment)->save();

        $otherCommentable = tap(new OtherCommentable())->save();
        $comment2 = tap((new Comment)->commentable()->associate($otherCommentable))->save();
        (new Like())->comment()->associate($comment2)->save();
    }

    public function test()
    {
        /** @var Post $post */
        $post = Post::first();
        $this->assertEquals(2, $post->commentLikes()->count());
    }
}

class Comment extends Model
{
    public $timestamps = false;

    public function commentable()
    {
        return $this->morphTo();
    }

    public function likes()
    {
        return $this->hasMany(Like::class);
    }
}

class Post extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected $withCount = ['comments'];

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function commentLikes()
    {
        return $this->through($this->comments())->has('likes');
    }

    public function texts()
    {
        return $this->hasMany(Text::class);
    }
}

class OtherCommentable extends Model
{
    public $timestamps = false;

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }
}

class Text extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    public function post()
    {
        return $this->belongsTo(Post::class);
    }
}

class Like extends Model
{
    public $timestamps = false;

    public function comment()
    {
        return $this->belongsTo(Comment::class);
    }
}
