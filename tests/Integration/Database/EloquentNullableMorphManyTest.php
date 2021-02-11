<?php

namespace Illuminate\Tests\Integration\Database\EloquentNullableMorphManyTest;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

/**
 * @group integration
 */
class EloquentNullableMorphManyTest extends DatabaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('posts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->timestamps();
        });

        Schema::create('articles', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->timestamps();
        });

        Schema::create('comments', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            // $table->nullableMorphs('commentable');
            $table->integer('commentable_id')->nullable();
            $table->string('commentable_type');
            $table->timestamps();
        });

        Carbon::setTestNow(null);
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

    public function testQueryIsNotCheckingForeignKeyWhenNull()
    {
        $post = new Post();
        $comment = $post->comments()->save(new Comment(['name' => 'bar']));
        $expectedSql = 'select * from "comments" where "comments"."commentable_id" is null and "comments"."commentable_type" = ?';

        $this->assertEquals($comment->id, Comment::first()->id);
        $this->assertEquals($expectedSql, $post->comments()->toSql());
    }

    public function testQueryIsCheckingForeignKeyWhenNotNull()
    {
        $post = Post::create(['title' => 'foo']);
        $comment = $post->comments()->save(new Comment(['name' => 'bar']));
        $expectedSql = 'select * from "comments" where "comments"."commentable_id" = ? and "comments"."commentable_id" is not null and "comments"."commentable_type" = ?';

        $this->assertEquals($post->id, Post::first()->id);
        $this->assertEquals($comment->id, Comment::first()->id);
        $this->assertEquals($comment->commentable->id, $post->id);
        $this->assertEquals($expectedSql, $post->comments()->toSql());
    }

    public function testMorphManyQueryIsAlwaysCheckingForeignKeyIsNotNull()
    {
        $article = new Article();
        $comment = $article->comments()->save(new Comment(['name' => 'bar']));
        $badSql = 'select * from "comments" where "comments"."commentable_id" is null and "comments"."commentable_id" is not null and "comments"."commentable_type" = ?';

        $this->assertEquals($comment->id, Comment::first()->id);
        $this->assertEquals($badSql, $article->comments()->toSql());
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
        return $this->nullableMorphMany(Comment::class, 'commentable');
    }
}

class Article extends Model
{
    public $table = 'articles';
    public $timestamps = true;
    protected $guarded = [];

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
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
