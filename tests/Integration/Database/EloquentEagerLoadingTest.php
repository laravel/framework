<?php

namespace Illuminate\Tests\Integration\Database\EloquentEagerLoadingTest;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

class EloquentEagerLoadingTest extends DatabaseTestCase
{
    protected function defineDatabaseMigrationsAfterDatabaseRefreshed()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->string('content');
            $table->unsignedInteger('user_id');
        });

        Schema::create('comments', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->string('content');
            $table->unsignedInteger('post_id');
        });

        Schema::create('tags', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('comment_id');
        });

        $user = User::create();
        $post = $user->posts()->create([
            'title' => 'post title',
            'content' => 'post content',
        ]);
        $comment = $post->comments()->create([
            'title' => 'comment title',
            'content' => 'comment content',
        ]);
        $comment->tags()->create([]);
        $comment->tags()->create([]);
    }

    public function testEagerLoadingWithArrayNotation()
    {
        $users = User::query()
            ->with([
                'posts:id,content,user_id' => [
                    'comments:id,title,post_id' => [
                        'tags',
                    ],
                ],
            ])
            ->get();

        $this->assertCount(1, $users);
        $this->assertTrue($users[0]->relationLoaded('posts'));
        $this->assertCount(1, $users[0]->posts);
        $this->assertNull($users[0]->posts[0]->title);
        $this->assertSame('post content', $users[0]->posts[0]->content);
        $this->assertTrue($users[0]->posts[0]->relationLoaded('comments'));
        $this->assertCount(1, $users[0]->posts[0]->comments);
        $this->assertSame('comment title', $users[0]->posts[0]->comments[0]->title);
        $this->assertNull($users[0]->posts[0]->comments[0]->content);
        $this->assertTrue($users[0]->posts[0]->comments[0]->relationLoaded('tags'));
        $this->assertCount(2, $users[0]->posts[0]->comments[0]->tags);
    }
}

class User extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    public function posts()
    {
        return $this->hasMany(Post::class);
    }
}

class Post extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }
}

class Comment extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    public function tags()
    {
        return $this->hasMany(Tag::class);
    }
}

class Tag extends Model
{
    protected $guarded = [];

    public $timestamps = false;
}
