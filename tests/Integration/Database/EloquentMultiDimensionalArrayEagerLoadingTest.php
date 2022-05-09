<?php

namespace Illuminate\Tests\Integration\Database\EloquentMultiDimensionalArrayEagerLoadingTest;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

class EloquentMultiDimensionalArrayEagerLoadingTest extends DatabaseTestCase
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

        Schema::create('images', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('post_id');
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
        $posts = $user->posts()->createMany([
            [
                'title' => '1. post title',
                'content' => '1. post content',
            ],
            [
                'title' => '2. post title',
                'content' => '2. post content',
            ],
        ]);
        $posts->map->image()->each->create();
        $comments = $posts->map->comments()->map->create([
            'title' => 'comment title',
            'content' => 'comment content',
        ]);
        $comments->map->tags()->each->create();
        $comments->map->tags()->each->create();
        $comments->map->tags()->each->create();
    }

    public function testEagerLoadingWithArrayNotation()
    {
        $users = User::query()
            ->with([
                'posts' => [
                    'comments' => [
                        'tags',
                    ],
                    'image',
                ],
            ])->get();

        $this->assertCount(1, $users);
        $this->assertTrue($users[0]->relationLoaded('posts'));
        $this->assertCount(2, $users[0]->posts);
        $this->assertTrue($users[0]->posts[0]->isNot($users[0]->posts[1]));
        $this->assertTrue($users[0]->posts->every->relationLoaded('image')); // failing
        $this->assertCount(2, $users[0]->posts->map->image);
        $this->assertTrue($users[0]->posts[0]->image->isNot($users[0]->posts[1]->image));
        $this->assertTrue($users[0]->posts->every->relationLoaded('comments'));
        $this->assertCount(2, $users[0]->posts->flatMap->comments);
        $this->assertTrue($users[0]->posts[0]->comments[0]->isNot($users[0]->posts[1]->comments[0]));
        $this->assertTrue($users[0]->posts->flatMap->comments->every->relationLoaded('tags'));
        $this->assertCount(6, $users[0]->posts->flatMap->comments->flatMap->tags);
    }

    public function testItAppliesConstaintsViaClosuresAndContinueEagerLoading()
    {
        $users = User::query()
            ->with([
                'posts' => fn ($query) => $query->withCount('comments')->with([
                    'comments' => [
                        'tags',
                    ],
                ]),
            ])
            ->get();

        $this->assertCount(1, $users);
        $this->assertTrue($users[0]->relationLoaded('posts'));
        $this->assertCount(2, $users[0]->posts);
        $this->assertTrue($users[0]->posts->every(fn ($post) => $post->comments_count === 1));
        $this->assertTrue($users[0]->posts->every->relationLoaded('comments'));
        $this->assertCount(2, $users[0]->posts->flatMap->comments);
        $this->assertTrue($users[0]->posts->flatMap->comments->every->relationLoaded('tags'));
    }

    public function testItSpecifiesAttributesToSelectInKey()
    {
        $users = User::query()
            ->with([
                'posts:id,title,user_id' => [
                    'comments:id,content,post_id' => [
                        'tags',
                    ],
                ],
            ])
            ->get();

        $this->assertCount(1, $users);
        $this->assertTrue($users[0]->relationLoaded('posts'));
        $this->assertCount(2, $users[0]->posts);
        $this->assertTrue($users[0]->posts->every(fn ($post) => array_keys($post->getAttributes()) === ['id', 'title', 'user_id']));
        $this->assertTrue($users[0]->posts->every->relationLoaded('comments'));
        $this->assertCount(2, $users[0]->posts->flatMap->comments);
        $this->assertTrue($users[0]->posts->flatMap->comments->every(fn ($post) => array_keys($post->getAttributes()) === ['id', 'content', 'post_id']));
        $this->assertTrue($users[0]->posts->flatMap->comments->every->relationLoaded('tags'));
        $this->assertCount(6, $users[0]->posts->flatMap->comments->flatMap->tags);
    }

    public function testItMixesDotNotation()
    {
        $users = User::query()
            ->with([
                'posts' => [
                    'comments',
                ],
                'posts.image',
            ])
            ->get();

        $this->assertCount(1, $users);
        $this->assertTrue($users[0]->relationLoaded('posts'));
        $this->assertCount(2, $users[0]->posts);
        $this->assertTrue($users[0]->posts->every->relationLoaded('comments'));
        $this->assertCount(2, $users[0]->posts->flatMap->comments);
        $this->assertTrue($users[0]->posts->every->relationLoaded('image'));
        $this->assertCount(2, $users[0]->posts->map->image);
    }

    public function testItMixesConstraintsFromDotNotation()
    {
        $users = User::query()
            ->with([
                // TODO: add attribute selection to both of these.
                'posts.comments' => fn ($query) => $query->with('tags'),
                'posts' => [
                    'comments' => fn ($query) => $query->withCount('tags'),
                ],
            ])
            ->get();

        $this->assertCount(1, $users);
        $this->assertTrue($users[0]->relationLoaded('posts'));
        $this->assertCount(2, $users[0]->posts);
        $this->assertTrue($users[0]->posts->every->relationLoaded('comments'));
        $this->assertCount(2, $users[0]->posts->flatMap->comments);
        $this->assertTrue($users[0]->posts->flatMap->comments->every(fn ($comment) => $comment->tags_count === 3));
        $this->assertTrue($users[0]->posts->flatMap->comments->every->relationLoaded('tags'));
        $this->assertCount(6, $users[0]->posts->flatMap->comments->flatMap->tags);
    }

    public function testItHandlesNestedRelationshipsWithTheSameName()
    {
        DB::enableQueryLog();
        User::with('posts:id')->with('posts:content')->get();
        //
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

    public function image()
    {
        return $this->hasOne(Image::class);
    }
}

class Image extends Model
{
    public $timestamps = false;

    protected $guarded = [];
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
