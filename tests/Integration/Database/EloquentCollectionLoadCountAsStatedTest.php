<?php

namespace App\Integration\Database;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

class EloquentCollectionLoadCountAsStatedTest extends DatabaseTestCase
{
    protected function defineDatabaseMigrationsAfterDatabaseRefreshed()
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('some_default_value');
            $table->softDeletes();
        });

        Schema::create('comments', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('post_id');
        });

        Schema::create('likes', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('post_id');
        });

        Schema::create('comment_likes', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('comment_id');
        });

        $post = Post::create();
        $post->comments()->saveMany([new Comment, new Comment]);

        tap($post->comments[0], function ($comment) {
            $comment->likes()->save(new CommentLike);
            $comment->likes()->save(new CommentLike);
        });
        tap($post->comments[1], fn($comment) => $comment->likes()->save(new CommentLike));

        $post->likes()->save(new Like);

        Post::create();
    }

    public function testLoadCountAsStated()
    {
        $posts = Post::all();

        DB::enableQueryLog();

        $posts->loadCountAsStated('commentWithLikes');

        $this->assertCount(1, DB::getQueryLog());
        $this->assertSame('3', $posts[0]->comment_with_likes_count);
        $this->assertSame('0', $posts[1]->comment_with_likes_count);
        $this->assertSame('3', $posts[0]->getOriginal('comment_with_likes_count'));
    }

    public function testLoadCountAsStatedWithSameModels()
    {
        $posts = Post::all()->push(Post::first());

        DB::enableQueryLog();

        $posts->loadCountAsStated('commentWithLikes');

        $this->assertCount(1, DB::getQueryLog());
        $this->assertEquals('3', $posts[0]->comment_with_likes_count);
        $this->assertEquals('0', $posts[1]->comment_with_likes_count);
        $this->assertEquals('3', $posts[2]->comment_with_likes_count);
    }

    public function testLoadCountAsStatedOnDeletedModels()
    {
        $posts = Post::all()->each->delete();

        DB::enableQueryLog();

        $posts->loadCountAsStated('commentWithLikes');

        $this->assertCount(1, DB::getQueryLog());
        $this->assertSame('3', $posts[0]->comment_with_likes_count);
        $this->assertSame('0', $posts[1]->comment_with_likes_count);
    }

    public function testLoadCountAsStatedWithArrayOfRelations()
    {
        $posts = Post::all();

        DB::enableQueryLog();

        $posts->loadCountAsStated(['comments', 'commentWithLikes', 'likes']);

        $this->assertCount(1, DB::getQueryLog());
        $this->assertSame('2', $posts[0]->comments_count);
        $this->assertSame('3', $posts[0]->comment_with_likes_count);
        $this->assertSame('1', $posts[0]->likes_count);
        $this->assertSame('0', $posts[1]->comments_count);
        $this->assertSame('0', $posts[1]->comment_with_likes_count);
        $this->assertSame('0', $posts[1]->likes_count);
    }

    public function testLoadCountAsStatedDoesNotOverrideAttributesWithDefaultValue()
    {
        $post = Post::first();
        $post->some_default_value = 200;

        Collection::make([$post])->loadCountAsStated('commentWithLikes');

        $this->assertSame(200, $post->some_default_value);
        $this->assertSame('3', $post->comment_with_likes_count);
    }
}

class Post extends Model
{
    use SoftDeletes;

    protected $attributes = [
        'some_default_value' => 100,
    ];

    public $timestamps = false;

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function commentWithLikes()
    {
        return $this->comments()->join('comment_likes', 'comment_likes.comment_id', 'comments.id');
    }

    public function likes()
    {
        return $this->hasMany(Like::class);
    }
}

class Comment extends Model
{
    public $timestamps = false;

    public function likes()
    {
        return $this->hasMany(CommentLike::class);
    }
}

class Like extends Model
{
    public $timestamps = false;
}

class CommentLike extends Model
{
    public $timestamps = false;
}
