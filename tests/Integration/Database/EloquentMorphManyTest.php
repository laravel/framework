<?php

namespace Illuminate\Tests\Integration\Database\EloquentMorphManyTest;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

class EloquentMorphManyTest extends DatabaseTestCase
{
    protected function afterRefreshingDatabase()
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->timestamps();
        });

        Schema::create('comments', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->integer('commentable_id');
            $table->string('commentable_type');
            $table->timestamps();
        });
    }

    public function testUpdateModelWithDefaultWithCount()
    {
        $post = Post::create(['title' => Str::random()]);

        $post->update(['title' => 'new name']);

        $this->assertSame('new name', $post->title);
    }

    public function test_self_referencing_existence_query()
    {
        $post = Post::create(['title' => 'foo']);

        $comment = tap((new Comment(['name' => 'foo']))->commentable()->associate($post))->save();

        (new Comment(['name' => 'bar']))->commentable()->associate($comment)->save();

        $comments = Comment::has('replies')->get();

        $this->assertEquals([1], $comments->pluck('id')->all());
    }

    public function testCanMorphOne()
    {
        $post = Post::create(['title' => 'Your favorite book by C.S. Lewis']);

        Carbon::setTestNow('1990-02-02 12:00:00');
        $oldestComment = tap((new Comment(['name' => 'The Allegory Of Love']))->commentable()->associate($post))->save();

        Carbon::setTestNow('2000-07-02 09:00:00');
        tap((new Comment(['name' => 'The Screwtape Letters']))->commentable()->associate($post))->save();

        Carbon::setTestNow('2022-01-01 00:00:00');
        $latestComment = tap((new Comment(['name' => 'The Silver Chair']))->commentable()->associate($post))->save();

        $this->assertInstanceOf(MorphOne::class, $post->comments()->one());

        $this->assertEquals($latestComment->id, $post->latestComment->id);
        $this->assertEquals($oldestComment->id, $post->oldestComment->id);
    }
}

class Post extends Model
{
    public $table = 'posts';
    public $timestamps = true;
    protected $guarded = [];
    protected $withCount = ['comments'];

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function latestComment(): MorphOne
    {
        return $this->comments()->one()->latestOfMany();
    }

    public function oldestComment(): MorphOne
    {
        return $this->comments()->one()->oldestOfMany();
    }
}

class Comment extends Model
{
    public $table = 'comments';
    public $timestamps = true;
    protected $guarded = [];

    public function commentable()
    {
        return $this->morphTo();
    }

    public function replies()
    {
        return $this->morphMany(self::class, 'commentable');
    }
}
