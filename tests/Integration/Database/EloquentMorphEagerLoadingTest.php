<?php

namespace Illuminate\Tests\Integration\Database\EloquentMorphEagerLoadingTest;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

class EloquentMorphEagerLoadingTest extends DatabaseTestCase
{
    protected function defineDatabaseMigrationsAfterDatabaseRefreshed()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->softDeletes();
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->increments('post_id');
            $table->unsignedInteger('user_id');
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
        $user2 = User::forceCreate(['deleted_at' => now()]);

        $post = tap((new Post)->user()->associate($user))->save();

        $video = Video::create();

        (new Comment)->commentable()->associate($post)->save();
        (new Comment)->commentable()->associate($video)->save();
        (new Comment)->commentable()->associate($user2)->save();
    }

    public function testWithMorphLoading()
    {
        $comments = Comment::query()
            ->with(['commentable' => function (MorphTo $morphTo) {
                $morphTo->morphWith([Post::class => ['user']]);
            }])
            ->get();

        $this->assertCount(3, $comments);

        $this->assertTrue($comments[0]->relationLoaded('commentable'));
        $this->assertTrue($comments[0]->commentable->relationLoaded('user'));
        $this->assertTrue($comments[1]->relationLoaded('commentable'));
        $this->assertTrue($comments[2]->relationLoaded('commentable'));
        $this->assertNull($comments[2]->commentable);
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

    public function testMorphLoadingMixedWithTrashedRelations()
    {
        $comments = Comment::query()
            ->with('commentable_with_trashed')
            ->get();

        $this->assertCount(3, $comments);

        $this->assertTrue($comments[0]->relationLoaded('commentable_with_trashed'));
        $this->assertNull($comments[0]->getRelation('commentable_with_trashed'));
        $this->assertTrue($comments[1]->relationLoaded('commentable_with_trashed'));
        $this->assertTrue($comments[2]->relationLoaded('commentable_with_trashed'));
        $this->assertNull($comments[2]->getRelation('commentable_with_trashed'));
    }
}

class Comment extends Model
{
    public $timestamps = false;

    public function commentable()
    {
        return $this->morphTo();
    }

    public function commentable_with_trashed()
    {
        return $this->morphTo('commentable')->withTrashed();
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
    use SoftDeletes;

    public $timestamps = false;
}

class Video extends Model
{
    public $timestamps = false;
    protected $primaryKey = 'video_id';
}
