<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EloquentForTest extends DatabaseTestCase
{
    protected function defineDatabaseMigrationsAfterDatabaseRefreshed()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->timestamps();
        });

        Schema::create('comments', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->integer('post_id');
            $table->string('content');
            $table->timestamps();
        });
    }

    public function testForCanBeUsedOnBuilderCreate()
    {
        $user = User::create(['name' => 'My name']);
        $post = Post::create(['title' => 'My title']);

        $comment = Comment::for($user)
            ->for($post, 'blogPost')
            ->create([
                'content' => 'hello',
            ])
            ->fresh();

        $this->assertSame('hello', $comment->content);

        $this->assertSame($user->id, $comment->user_id);
        $this->assertInstanceOf(User::class, $comment->user);

        $this->assertSame($post->id, $comment->post_id);
        $this->assertInstanceOf(Post::class, $comment->blogPost);
    }

    public function testForCanBeUsedOnBuilderMake()
    {
        $user = User::query()->create(['name' => 'My name']);
        $post = Post::create(['title' => 'My title']);

        $comment = Comment::query()
            ->for($user)
            ->for($post, 'blogPost')
            ->make([
                'content' => 'hello',
            ]);

        $this->assertSame('hello', $comment->content);

        $this->assertSame($user->id, $comment->user_id);
        $this->assertInstanceOf(User::class, $comment->user);

        $this->assertSame($post->id, $comment->post_id);
        $this->assertInstanceOf(Post::class, $comment->blogPost);
    }

    public function testForCanBeUsedOnFirstOrNewAndIsNotAppliedIfTheModelAlreadyExists()
    {
        $user = User::create(['name' => 'My name']);
        $anotherUser = User::create(['name' => 'Another name']);
        $post = Post::create(['title' => 'My title']);

        Comment::create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'content' => 'Hello',
        ]);

        $comment = Comment::query()
            ->for($anotherUser)
            ->firstOrNew([
                'user_id' => $user->id,
            ], [
                'value' => 'Goodbye',
            ]);

        $this->assertSame($user->id, $comment->user_id);
    }

    public function testForCanBeUsedOnFirstOrNewAndIsAppliedIfTheModelDoesNotAlreadyExist()
    {
        $user = User::create(['name' => 'My name']);
        $anotherUser = User::create(['name' => 'Another name']);
        $post = Post::create(['title' => 'My title']);

        Comment::create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'content' => 'Hello',
        ]);

        $comment = Comment::query()
            ->for($anotherUser)
            ->firstOrNew([
                'user_id' => 123,
            ], [
                'value' => 'Goodbye',
            ]);

        $this->assertSame($anotherUser->id, $comment->user_id);
    }

    public function testForCanBeUsedOnFirstOrCreateIfTheModelAlreadyExists()
    {
        $user = User::create(['name' => 'My name']);
        $anotherUser = User::create(['name' => 'Another name']);
        $post = Post::create(['title' => 'My title']);
        $anotherPost = Post::create(['title' => 'Another title']);

        Comment::create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'content' => 'Hello',
        ]);

        $comment = Comment::query()
            ->for($anotherUser)
            ->for($anotherPost, 'blogPost')
            ->firstOrCreate([
                'user_id' => $user->id,
            ], [
                'content' => 'Goodbye',
            ]);

        $this->assertSame($user->id, $comment->user_id);
        $this->assertSame($post->id, $comment->post_id);
    }

    public function testForCanBeUsedOnFirstOrCreateIfTheModelDoesNotAlreadyExist()
    {
        $user = User::create(['name' => 'My name']);
        $anotherUser = User::create(['name' => 'Another name']);
        $post = Post::create(['title' => 'My title']);
        $anotherPost = Post::create(['title' => 'Another title']);

        Comment::create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'content' => 'Hello',
        ]);

        $comment = Comment::query()
            ->for($anotherUser)
            ->for($anotherPost, 'blogPost')
            ->firstOrCreate([
                'user_id' => 123,
            ], [
                'content' => 'Goodbye',
            ]);

        $this->assertSame($anotherUser->id, $comment->user_id);
        $this->assertInstanceOf(User::class, $comment->user);

        $this->assertSame($anotherPost->id, $comment->post_id);
        $this->assertInstanceOf(Post::class, $comment->blogPost);
    }

    public function testForCanBeUsedOnBuilderUpdate()
    {
        $user = User::create(['name' => 'My name']);
        $anotherUser = User::create(['name' => 'Another name']);
        $post = Post::create(['title' => 'My title']);
        $anotherPost = Post::create(['title' => 'Another title']);

        $commentOne = Comment::create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'content' => 'Hello1',
        ]);

        $commentTwo = Comment::create([
            'user_id' => $user->id,
            'post_id' => $anotherPost->id,
            'content' => 'Hello2',
        ]);

        Comment::query()
            ->for($anotherUser)
            ->update([
                'content' => 'Hello3',
            ]);

        $commentOne->refresh();
        $commentTwo->refresh();

        $this->assertSame($anotherUser->id, $commentOne->user_id);
        $this->assertSame('Hello3', $commentOne->content);

        $this->assertSame($anotherUser->id, $commentTwo->user_id);
        $this->assertSame('Hello3', $commentTwo->content);
    }

    public function testForCanBeUsedOnUpdateOrCreate()
    {
        $user = User::create(['name' => 'My name']);
        $anotherUser = User::create(['name' => 'Another name']);
        $post = Post::create(['title' => 'My title']);
        $anotherPost = Post::create(['title' => 'Another title']);

        Comment::create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'content' => 'Hello',
        ]);

        $comment = Comment::query()
            ->for($anotherUser)
            ->for($anotherPost, 'blogPost')
            ->updateOrCreate([
                'user_id' => 123,
            ], [
                'content' => 'Goodbye',
            ]);

        $this->assertSame($anotherUser->id, $comment->user_id);
        $this->assertInstanceOf(User::class, $comment->user);

        $this->assertSame($anotherPost->id, $comment->post_id);
        $this->assertInstanceOf(Post::class, $comment->blogPost);
    }

    public function testForCanBeUsedOnForceCreate()
    {
        $user = User::create(['name' => 'My name']);
        $post = Post::create(['title' => 'My title']);

        $comment = Comment::for($user)
            ->for($post, 'blogPost')
            ->forceCreate([
                'content' => 'hello',
            ])
            ->fresh();

        $this->assertSame('hello', $comment->content);

        $this->assertSame($user->id, $comment->user_id);
        $this->assertInstanceOf(User::class, $comment->user);

        $this->assertSame($post->id, $comment->post_id);
        $this->assertInstanceOf(Post::class, $comment->blogPost);
    }

    public function testForCanBeUsedOnModelUpdate()
    {
        $user = User::create(['name' => 'My name']);
        $anotherUser = User::create(['name' => 'Another name']);
        $post = Post::create(['title' => 'My title']);
        $anotherPost = Post::create(['title' => 'Another title']);

        $comment = Comment::create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'content' => 'Hello',
        ]);

        // Make sure this comment isn't updated accidentally.
        $anotherComment = Comment::create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'content' => 'Hello123',
        ]);

        $comment->for($anotherUser)
            ->for($anotherPost, 'blogPost')
            ->update([
                'content' => 'goodbye',
            ]);

        $comment->refresh();
        $anotherComment->refresh();

        $this->assertSame('goodbye', $comment->content);

        $this->assertSame($anotherUser->id, $comment->user_id);
        $this->assertInstanceOf(User::class, $comment->user);

        $this->assertSame($anotherPost->id, $comment->post_id);
        $this->assertInstanceOf(Post::class, $comment->blogPost);

        $this->assertSame('Hello123', $anotherComment->content);
        $this->assertSame($user->id, $anotherComment->user_id);
        $this->assertSame($post->id, $anotherComment->post_id);
    }
}

class User extends Model
{
    public $table = 'users';
    protected $guarded = [];

    public function posts()
    {
        return $this->hasMany(Post::class);
    }
}

class Post extends Model
{
    public $table = 'posts';
    protected $guarded = [];

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }
}

class Comment extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function blogPost(): BelongsTo
    {
        return $this->belongsTo(Post::class, 'post_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
