<?php

namespace Illuminate\Tests\Database;

use PHPUnit\Framework\TestCase;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model as Eloquent;

class DatabaseEloquentPolymorphicIntegrationTest extends TestCase
{
    public function setUp()
    {
        $db = new DB;

        $db->addConnection([
            'driver'    => 'sqlite',
            'database'  => ':memory:',
        ]);

        $db->bootEloquent();
        $db->setAsGlobal();

        $this->createSchema();
    }

    /**
     * Setup the database schema.
     *
     * @return void
     */
    public function createSchema()
    {
        $this->schema()->create('users', function ($table) {
            $table->increments('id');
            $table->string('email')->unique();
            $table->timestamps();
        });

        $this->schema()->create('posts', function ($table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->string('title');
            $table->text('body');
            $table->timestamps();
        });

        $this->schema()->create('comments', function ($table) {
            $table->increments('id');
            $table->integer('commentable_id');
            $table->string('commentable_type');
            $table->integer('user_id');
            $table->text('body');
            $table->timestamps();
        });

        $this->schema()->create('likes', function ($table) {
            $table->increments('id');
            $table->integer('likeable_id');
            $table->string('likeable_type');
            $table->timestamps();
        });
    }

    /**
     * Tear down the database schema.
     *
     * @return void
     */
    public function tearDown()
    {
        $this->schema()->drop('users');
        $this->schema()->drop('posts');
        $this->schema()->drop('comments');
    }

    public function testItLoadsRelationshipsAutomatically()
    {
        $this->seedData();

        $like = TestLikeWithSingleWith::first();

        $this->assertTrue($like->relationLoaded('likeable'));
        $this->assertEquals(TestComment::first(), $like->likeable);
    }

    public function testItLoadsChainedRelationshipsAutomatically()
    {
        $this->seedData();

        $like = TestLikeWithSingleWith::first();

        $this->assertTrue($like->likeable->relationLoaded('commentable'));
        $this->assertEquals(TestPost::first(), $like->likeable->commentable);
    }

    public function testItLoadsNestedRelationshipsAutomatically()
    {
        $this->seedData();

        $like = TestLikeWithNestedWith::first();

        $this->assertTrue($like->relationLoaded('likeable'));
        $this->assertTrue($like->likeable->relationLoaded('owner'));

        $this->assertEquals(TestUser::first(), $like->likeable->owner);
    }

    public function testItLoadsNestedRelationshipsOnDemand()
    {
        $this->seedData();

        $like = TestLike::with('likeable.owner')->first();

        $this->assertTrue($like->relationLoaded('likeable'));
        $this->assertTrue($like->likeable->relationLoaded('owner'));

        $this->assertEquals(TestUser::first(), $like->likeable->owner);
    }

    /**
     * Helpers...
     */
    protected function seedData()
    {
        $taylor = TestUser::create(['id' => 1, 'email' => 'taylorotwell@gmail.com']);

        $taylor->posts()->create(['title' => 'A title', 'body' => 'A body'])
            ->comments()->create(['body' => 'A comment body', 'user_id' => 1])
            ->likes()->create([]);
    }

    /**
     * Get a database connection instance.
     *
     * @return Connection
     */
    protected function connection()
    {
        return Eloquent::getConnectionResolver()->connection();
    }

    /**
     * Get a schema builder instance.
     *
     * @return Schema\Builder
     */
    protected function schema()
    {
        return $this->connection()->getSchemaBuilder();
    }
}

/**
 * Eloquent Models...
 */
class TestUser extends Eloquent
{
    protected $table = 'users';
    protected $guarded = [];

    public function posts()
    {
        return $this->hasMany(TestPost::class, 'user_id');
    }
}

/**
 * Eloquent Models...
 */
class TestPost extends Eloquent
{
    protected $table = 'posts';
    protected $guarded = [];

    public function comments()
    {
        return $this->morphMany(TestComment::class, 'commentable');
    }

    public function owner()
    {
        return $this->belongsTo(TestUser::class, 'user_id');
    }
}

/**
 * Eloquent Models...
 */
class TestComment extends Eloquent
{
    protected $table = 'comments';
    protected $guarded = [];
    protected $with = ['commentable'];

    public function owner()
    {
        return $this->belongsTo(TestUser::class, 'user_id');
    }

    public function commentable()
    {
        return $this->morphTo();
    }

    public function likes()
    {
        return $this->morphMany(TestLike::class, 'likeable');
    }
}

class TestLike extends Eloquent
{
    protected $table = 'likes';
    protected $guarded = [];

    public function likeable()
    {
        return $this->morphTo();
    }
}

class TestLikeWithSingleWith extends Eloquent
{
    protected $table = 'likes';
    protected $guarded = [];
    protected $with = ['likeable'];

    public function likeable()
    {
        return $this->morphTo();
    }
}

class TestLikeWithNestedWith extends Eloquent
{
    protected $table = 'likes';
    protected $guarded = [];
    protected $with = ['likeable.owner'];

    public function likeable()
    {
        return $this->morphTo();
    }
}
