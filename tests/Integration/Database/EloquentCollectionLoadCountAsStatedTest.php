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
        Schema::create('new_posts', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('some_default_value');
            $table->softDeletes();
        });

        Schema::create('new_comments', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('new_post_id');
        });

        Schema::create('new_likes', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('new_post_id');
        });

        Schema::create('new_comment_likes', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('new_comment_id');
        });

        $post = NewPost::create();
        $post->comments()->saveMany([new NewComment, new NewComment]);

        tap($post->comments[0], function ($comment) {
            $comment->likes()->save(new NewCommentLike);
            $comment->likes()->save(new NewCommentLike);
        });
        tap($post->comments[1], fn ($comment) => $comment->likes()->save(new NewCommentLike));

        $post->likes()->save(new NewLike);

        NewPost::create();
    }

    public function testLoadCountAsStated()
    {
        $posts = NewPost::all();

        DB::enableQueryLog();

        $posts->loadCountAsStated('commentWithLikes');

        $this->assertCount(1, DB::getQueryLog());
        $this->assertSame('3', $posts[0]->comment_with_likes_count);
        $this->assertSame('0', $posts[1]->comment_with_likes_count);
        $this->assertSame('3', $posts[0]->getOriginal('comment_with_likes_count'));
    }

    public function testLoadCountAsStatedWithSameModels()
    {
        $posts = NewPost::all()->push(NewPost::first());

        DB::enableQueryLog();

        $posts->loadCountAsStated('commentWithLikes');

        $this->assertCount(1, DB::getQueryLog());
        $this->assertEquals('3', $posts[0]->comment_with_likes_count);
        $this->assertEquals('0', $posts[1]->comment_with_likes_count);
        $this->assertEquals('3', $posts[2]->comment_with_likes_count);
    }

    public function testLoadCountAsStatedOnDeletedModels()
    {
        $posts = NewPost::all()->each->delete();

        DB::enableQueryLog();

        $posts->loadCountAsStated('commentWithLikes');

        $this->assertCount(1, DB::getQueryLog());
        $this->assertSame('3', $posts[0]->comment_with_likes_count);
        $this->assertSame('0', $posts[1]->comment_with_likes_count);
    }

    public function testLoadCountAsStatedWithArrayOfRelations()
    {
        $posts = NewPost::all();

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
        $post = NewPost::first();
        $post->some_default_value = 200;

        Collection::make([$post])->loadCountAsStated('commentWithLikes');

        $this->assertSame(200, $post->some_default_value);
        $this->assertSame('3', $post->comment_with_likes_count);
    }
}

class NewPost extends Model
{
    use SoftDeletes;

    protected $attributes = [
        'some_default_value' => 100,
    ];

    public $timestamps = false;

    public function comments()
    {
        return $this->hasMany(NewComment::class);
    }

    public function commentWithLikes()
    {
        return $this->comments()->join('new_comment_likes', 'new_comment_likes.new_comment_id', 'new_comments.id');
    }

    public function likes()
    {
        return $this->hasMany(NewLike::class);
    }
}

class NewComment extends Model
{
    public $timestamps = false;

    public function likes()
    {
        return $this->hasMany(NewCommentLike::class);
    }
}

class NewLike extends Model
{
    public $timestamps = false;
}

class NewCommentLike extends Model
{
    public $timestamps = false;
}
