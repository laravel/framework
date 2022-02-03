<?php

namespace Illuminate\Tests\Integration\Database\EloquentMorphEagerLoadingTest;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

class EloquentMorphEagerLoadingTest extends DatabaseTestCase
{
    protected function defineDatabaseMigrationsAfterDatabaseRefreshed()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->increments('post_id');
            $table->unsignedInteger('user_id');
            $table->string('secret_comment')->nullable();
        });

        Schema::create('videos', function (Blueprint $table) {
            $table->increments('video_id');
        });

        Schema::create('comments', function (Blueprint $table) {
            $table->increments('id');
            $table->string('commentable_type');
            $table->integer('commentable_id');
        });

        $user = User::create();

        $post = tap((new Post)->user()->associate($user))->save();

        $video = Video::create();

        (new Comment)->commentable()->associate($post)->save();
        (new Comment)->commentable()->associate($video)->save();
    }

    public function testWithMorphLoading()
    {
        $comments = Comment::query()
            ->with(['commentable' => function (MorphTo $morphTo) {
                $morphTo->morphWith([Post::class => ['user']]);
            }])
            ->get();

        $this->assertTrue($comments[0]->relationLoaded('commentable'));
        $this->assertTrue($comments[0]->commentable->relationLoaded('user'));
        $this->assertTrue($comments[1]->relationLoaded('commentable'));
    }

    public function testWithMorphLoadingWithSingleRelation()
    {
        $comments = Comment::query()
            ->with(['commentable' => function (MorphTo $morphTo) {
                $morphTo->morphWith([Post::class => 'user']);
            }])
            ->get();

        $this->assertTrue($comments[0]->relationLoaded('commentable'));
        $this->assertTrue($comments[0]->commentable->relationLoaded('user'));
    }

    public function testMorphOnlyColumns()
    {
        $comments = Comment::query()
            ->with(['commentable' => function (MorphTo $morphTo) {
                $morphTo->morphColumns([Post::class => ['post_id', 'user_id']]);
                $morphTo->morphWith([Post::class => 'user']);
            }])
            ->get();

        $this->assertTrue($comments[0]->relationLoaded('commentable'));
        $this->assertTrue($comments[0]->commentable->relationLoaded('user'));

        $morphColumns = collect($comments[0]->commentable->getAttributes())->keys();
        $this->assertEquals($morphColumns->intersect(['post_id', 'user_id'])->count(), 2);
        $this->assertTrue($morphColumns->doesntContain('secret_comment'));
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
    protected $primaryKey = 'post_id';

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

class User extends Model
{
    public $timestamps = false;
}

class Video extends Model
{
    public $timestamps = false;
    protected $primaryKey = 'video_id';
}
