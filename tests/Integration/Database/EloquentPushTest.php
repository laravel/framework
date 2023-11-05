<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Mockery;

class EloquentPushTest extends DatabaseTestCase
{
    protected function defineDatabaseMigrationsAfterDatabaseRefreshed()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->unsignedInteger('user_id');
        });

        Schema::create('post_details', function (Blueprint $table) {
            $table->increments('id');
            $table->string('description');
            $table->unsignedInteger('post_id');
        });

        Schema::create('comments', function (Blueprint $table) {
            $table->increments('id');
            $table->string('comment');
            $table->nullableMorphs('commentable');
        });
    }

    public function testPushMethodSavesTheRelationshipsRecursively()
    {
        $user = new UserX;
        $user->name = 'Test';
        $user->save();
        $user->posts()->create(['title' => 'Test title']);

        $post = PostX::firstOrFail();
        $post->comments()->create(['comment' => 'Test comment']);

        $user = $user->fresh();
        $user->name = 'Test 1';
        $user->posts[0]->title = 'Test title 1';
        $user->posts[0]->comments[0]->comment = 'Test comment 1';
        $user->push();

        $this->assertSame(1, UserX::count());
        $this->assertSame('Test 1', UserX::firstOrFail()->name);
        $this->assertSame(1, PostX::count());
        $this->assertSame('Test title 1', PostX::firstOrFail()->title);
        $this->assertSame(1, CommentX::count());
        $this->assertSame('Test comment 1', CommentX::firstOrFail()->comment);
    }

    public function testPushSavesAHasOneRelationship()
    {
        $user = UserX::create(['name' => 'Mateus']);
        $post = PostX::make(['title' => 'Test title', 'user_id' => $user->id]);
        $details = PostDetails::make(['description' => 'Test description']);

        $post->details = $details;
        $post->push();

        $this->assertEquals(1, $post->id);
        $this->assertEquals(1, $details->id);
        $this->assertTrue($details->fresh()->post->is($post));
    }

    public function testPushSavesAHasManyRelationship()
    {
        UserX::create(['name' => 'First test']); // So user starts at ID 2
        $user = UserX::make(['name' => 'Test']);
        $post = PostX::make(['title' => 'Test title']);
        $user->posts->push($post);

        $user->push();

        $this->assertEquals(2, $user->id);
        $this->assertEquals(1, $post->id);
        $this->assertTrue($post->fresh()->user->is($user));
    }

    public function testPushSavesABelongsToRelationship()
    {
        $post = PostX::make(['title' => 'Test title']);
        $post->user()->associate($user = UserX::make(['name' => 'Test']));

        $post->push();

        $this->assertEquals(1, $user->id);
        $this->assertEquals(1, $post->id);
        $this->assertTrue($post->fresh()->user->is($user));
    }

    public function testPushSavesAMorphOneRelationship()
    {
        $user = UserX::create(['name' => 'Mateus']);
        $post = PostX::make(['title' => 'Test title', 'user_id' => $user->id]);
        $comment = CommentX::make(['comment' => 'Test comment']);
        $post->comment = $comment;

        $post->push();

        $this->assertEquals(1, $post->id);
        $this->assertEquals(1, $comment->id);
        $this->assertTrue($post->fresh()->comment->is($comment));
    }

    public function testPushSavesAMorphManyRelationship()
    {
        $user = UserX::create(['name' => 'Mateus']);
        $post = PostX::make(['title' => 'Test title', 'user_id' => $user->id]);
        $post->comments->push($comment = CommentX::make(['comment' => 'Test comment']));

        $post->push();

        $this->assertEquals(1, $post->id);
        $this->assertEquals(1, $comment->id);
        $this->assertTrue($post->comments->first()->is($comment));
    }

    public function testPushSavesAMorphToRelationship()
    {
        $user = UserX::create(['name' => 'Mateus']);
        $post = PostX::make(['title' => 'Test title', 'user_id' => $user->id]);
        $comment = CommentX::make(['comment' => 'Test comment']);
        $comment->commentable()->associate($post);
        $comment->push();

        $this->assertEquals(1, $post->id);
        $this->assertEquals(1, $comment->id);
        $this->assertTrue($comment->commentable->is($post));
    }

    public function testPushReturnsFalseIfBelongsToSaveFails()
    {
        $post = PostX::make(['title' => 'Test title']);
        $user = UserX::make(['name' => 'Test']);
        $user->setEventDispatcher($events = Mockery::mock(Dispatcher::class));
        $events->expects('until')->with('eloquent.saving: '.get_class($user), $user)->andReturns(false);

        $post->user()->associate($user);

        $this->assertFalse($post->push());
    }

    public function testPushReturnsFalseIfRelationshipsFail()
    {
        $post = PostX::make(['title' => 'Test title']);
        $user = UserX::make(['name' => 'Test']);
        Model::setEventDispatcher($events = Mockery::mock(Dispatcher::class));
        $events->makePartial();
        $events->expects('dispatch')->times(3)->andReturn();
        $events->expects('until')->times(3)->andReturn(true);
        $events->expects('until')->with('eloquent.saving: '.get_class($post), $post)->andReturn(false);
        $user->save();

        $user->posts->push($post);

        $this->assertFalse($user->push());
    }
}

class UserX extends Model
{
    public $timestamps = false;
    protected $guarded = [];
    protected $table = 'users';

    public function posts()
    {
        return $this->hasMany(PostX::class, 'user_id');
    }
}

class PostX extends Model
{
    public $timestamps = false;
    protected $guarded = [];
    protected $table = 'posts';

    public function details()
    {
        return $this->hasOne(PostDetails::class, 'post_id');
    }

    public function comment()
    {
        return $this->morphOne(CommentX::Class, 'commentable');
    }

    public function comments()
    {
        return $this->morphMany(CommentX::class, 'commentable');
    }

    public function user()
    {
        return $this->belongsTo(UserX::class, 'user_id');
    }
}

class PostDetails extends Model
{
    public $timestamps = false;
    protected $guarded = [];
    protected $table = 'post_details';

    public function post()
    {
        return $this->belongsTo(PostX::class, 'post_id');
    }
}

class CommentX extends Model
{
    public $timestamps = false;
    protected $guarded = [];
    protected $table = 'comments';

    public function commentable()
    {
        return $this->morphTo('commentable');
    }
}
