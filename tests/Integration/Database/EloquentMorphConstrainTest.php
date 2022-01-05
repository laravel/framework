<?php

namespace Illuminate\Tests\Integration\Database\EloquentMorphConstrainTest;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

class EloquentMorphConstrainTest extends DatabaseTestCase
{
    protected function defineDatabaseMigrationsAfterDatabaseRefreshed()
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->increments('id');
            $table->boolean('post_visible');
        });

        Schema::create('videos', function (Blueprint $table) {
            $table->increments('id');
            $table->boolean('video_visible');
        });

        Schema::create('comments', function (Blueprint $table) {
            $table->increments('id');
            $table->string('commentable_type');
            $table->integer('commentable_id');
        });

        $post1 = Post::create(['post_visible' => true]);
        (new Comment)->commentable()->associate($post1)->save();

        $post2 = Post::create(['post_visible' => false]);
        (new Comment)->commentable()->associate($post2)->save();

        $video1 = Video::create(['video_visible' => true]);
        (new Comment)->commentable()->associate($video1)->save();

        $video2 = Video::create(['video_visible' => false]);
        (new Comment)->commentable()->associate($video2)->save();
    }

    public function testMorphConstraints()
    {
        $comments = Comment::query()
            ->with(['commentable' => function (MorphTo $morphTo) {
                $morphTo->constrain([
                    Post::class => function ($query) {
                        $query->where('post_visible', true);
                    },
                    Video::class => function ($query) {
                        $query->where('video_visible', true);
                    },
                ]);
            }])
            ->get();

        $this->assertTrue($comments[0]->commentable->post_visible);
        $this->assertNull($comments[1]->commentable);
        $this->assertTrue($comments[2]->commentable->video_visible);
        $this->assertNull($comments[3]->commentable);
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
    protected $fillable = ['post_visible'];
    protected $casts = ['post_visible' => 'boolean'];
}

class Video extends Model
{
    public $timestamps = false;
    protected $fillable = ['video_visible'];
    protected $casts = ['video_visible' => 'boolean'];
}
