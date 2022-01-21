<?php

namespace Illuminate\Tests\Integration\Database\EloquentWhereHasTest;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

class EloquentWhereHasTest extends DatabaseTestCase
{
    protected function defineDatabaseMigrationsAfterDatabaseRefreshed()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
            $table->boolean('public');
        });

        Schema::create('texts', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('post_id');
            $table->text('content');
        });

        Schema::create('comments', function (Blueprint $table) {
            $table->increments('id');
            $table->string('commentable_type');
            $table->integer('commentable_id');
        });

        $user = User::create();
        $post = tap((new Post(['public' => true]))->user()->associate($user))->save();
        (new Comment)->commentable()->associate($post)->save();
        (new Text(['content' => 'test']))->post()->associate($post)->save();

        $user = User::create();
        $post = tap((new Post(['public' => false]))->user()->associate($user))->save();
        (new Comment)->commentable()->associate($post)->save();
        (new Text(['content' => 'test2']))->post()->associate($post)->save();
    }

    public function testWhereRelation()
    {
        $users = User::whereRelation('posts', 'public', true)->get();

        $this->assertEquals([1], $users->pluck('id')->all());
    }

    public function testOrWhereRelation()
    {
        $users = User::whereRelation('posts', 'public', true)->orWhereRelation('posts', 'public', false)->get();

        $this->assertEquals([1, 2], $users->pluck('id')->all());
    }

    public function testNestedWhereRelation()
    {
        $texts = User::whereRelation('posts.texts', 'content', 'test')->get();

        $this->assertEquals([1], $texts->pluck('id')->all());
    }

    public function testNestedOrWhereRelation()
    {
        $texts = User::whereRelation('posts.texts', 'content', 'test')->orWhereRelation('posts.texts', 'content', 'test2')->get();

        $this->assertEquals([1, 2], $texts->pluck('id')->all());
    }

    public function testWhereMorphRelation()
    {
        $comments = Comment::whereMorphRelation('commentable', '*', 'public', true)->get();

        $this->assertEquals([1], $comments->pluck('id')->all());
    }

    public function testOrWhereMorphRelation()
    {
        $comments = Comment::whereMorphRelation('commentable', '*', 'public', true)
            ->orWhereMorphRelation('commentable', '*', 'public', false)
            ->get();

        $this->assertEquals([1, 2], $comments->pluck('id')->all());
    }

    public function testWithCount()
    {
        $users = User::whereHas('posts', function ($query) {
            $query->where('public', true);
        })->get();

        $this->assertEquals([1], $users->pluck('id')->all());
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

    protected $guarded = [];

    protected $withCount = ['comments'];

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function texts()
    {
        return $this->hasMany(Text::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
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

class User extends Model
{
    public $timestamps = false;

    public function posts()
    {
        return $this->hasMany(Post::class);
    }
}
