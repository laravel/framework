<?php

namespace Illuminate\Tests\Integration\Database\EloquentMorphToLazyEagerLoadingTest;

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

/**
 * @group integration
 */
class EloquentMorphEagerLoadingTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
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

        $post = tap((new Post)->user()->associate($user))->save();

        $video = Video::create();

        (new Comment)->commentable()->associate($post)->save();
        (new Comment)->commentable()->associate($video)->save();
    }

    public function test_with_morph_loading()
    {
        $comments = Comment::query()
            ->with(['commentable' => function (MorphTo $morphTo) {
                $morphTo->withMorph(Post::class, ['user']);
            }])
            ->get();

        $this->assertTrue($comments[0]->relationLoaded('commentable'));
        $this->assertTrue($comments[0]->commentable->relationLoaded('user'));
        $this->assertTrue($comments[1]->relationLoaded('commentable'));
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
