<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model as Eloquent;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentPolymorphicIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        $db = new DB;

        $db->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
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
    protected function tearDown(): void
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

    public function testItLoadsNestedMorphRelationshipsOnDemand()
    {
        $this->seedData();

        TestPost::first()->likes()->create([]);

        $likes = TestLike::with('likeable.owner')->get()->loadMorph('likeable', [
            TestComment::class => ['commentable'],
            TestPost::class => 'comments',
        ]);

        $this->assertTrue($likes[0]->relationLoaded('likeable'));
        $this->assertTrue($likes[0]->likeable->relationLoaded('owner'));
        $this->assertTrue($likes[0]->likeable->relationLoaded('commentable'));

        $this->assertTrue($likes[1]->relationLoaded('likeable'));
        $this->assertTrue($likes[1]->likeable->relationLoaded('owner'));
        $this->assertTrue($likes[1]->likeable->relationLoaded('comments'));
    }

    public function testItLoadsNestedMorphRelationshipCountsOnDemand()
    {
        $this->seedData();

        TestPost::first()->likes()->create([]);
        TestComment::first()->likes()->create([]);

        $likes = TestLike::with('likeable.owner')->get()->loadMorphCount('likeable', [
            TestComment::class => ['likes'],
            TestPost::class => 'comments',
        ]);

        $this->assertTrue($likes[0]->relationLoaded('likeable'));
        $this->assertTrue($likes[0]->likeable->relationLoaded('owner'));
        $this->assertEquals(2, $likes[0]->likeable->likes_count);

        $this->assertTrue($likes[1]->relationLoaded('likeable'));
        $this->assertTrue($likes[1]->likeable->relationLoaded('owner'));
        $this->assertEquals(1, $likes[1]->likeable->comments_count);

        $this->assertTrue($likes[2]->relationLoaded('likeable'));
        $this->assertTrue($likes[2]->likeable->relationLoaded('owner'));
        $this->assertEquals(2, $likes[2]->likeable->likes_count);
    }

    public function testFindOrNewReturnsNewMorphModelWithMorphKeysSet()
    {
        $post = $this->createPost();

        $comment = $post->comments()->findOrNew(999);

        $this->assertFalse($comment->exists);
        $this->assertSame($post->id, $comment->commentable_id);
        $this->assertSame(TestPost::class, $comment->commentable_type);
    }

    public function testFindOrNewReturnsExistingMorphModel()
    {
        $post = $this->createPost();
        $existing = $post->comments()->create(['body' => 'foo', 'user_id' => 1]);

        $comment = $post->comments()->findOrNew($existing->id);

        $this->assertTrue($comment->exists);
        $this->assertSame($existing->id, $comment->id);
    }

    public function testFirstOrNewReturnsNewMorphModelWithMorphKeysSet()
    {
        $post = $this->createPost();

        $comment = $post->comments()->firstOrNew(['body' => 'foo'], ['user_id' => 1]);

        $this->assertFalse($comment->exists);
        $this->assertSame('foo', $comment->body);
        $this->assertSame(1, $comment->user_id);
        $this->assertSame($post->id, $comment->commentable_id);
        $this->assertSame(TestPost::class, $comment->commentable_type);
        $this->assertSame(0, DB::table('comments')->count());
    }

    public function testFirstOrCreateCreatesMorphModelWithMorphKeysSet()
    {
        $post = $this->createPost();

        $comment = $post->comments()->firstOrCreate(['body' => 'foo'], ['user_id' => 1]);

        $this->assertTrue($comment->wasRecentlyCreated);
        $this->assertMorphRow($post);

        $found = $post->comments()->firstOrCreate(['body' => 'foo'], ['user_id' => 2]);

        $this->assertFalse($found->wasRecentlyCreated);
        $this->assertSame(1, DB::table('comments')->count());
    }

    public function testFirstOrCreateIgnoresRowsOfAnotherMorphType()
    {
        $post = $this->createPost();
        DB::table('comments')->insert(['commentable_id' => $post->id, 'commentable_type' => 'other', 'body' => 'foo', 'user_id' => 9]);

        $comment = $post->comments()->firstOrCreate(['body' => 'foo'], ['user_id' => 1]);

        $this->assertTrue($comment->wasRecentlyCreated);
        $this->assertSame(TestPost::class, $comment->commentable_type);
        $this->assertSame(2, DB::table('comments')->count());
    }

    public function testCreateOrFirstCreatesMorphModelWithMorphKeysSet()
    {
        $post = $this->createPost();

        $comment = $post->comments()->createOrFirst(['body' => 'foo', 'user_id' => 1]);

        $this->assertTrue($comment->wasRecentlyCreated);
        $this->assertMorphRow($post);
    }

    public function testUpdateOrCreateCreatesMorphModelWithMorphKeysSet()
    {
        $post = $this->createPost();

        $comment = $post->comments()->updateOrCreate(['body' => 'foo'], ['user_id' => 1]);

        $this->assertTrue($comment->wasRecentlyCreated);
        $this->assertMorphRow($post);

        $updated = $post->comments()->updateOrCreate(['body' => 'foo'], ['user_id' => 2]);

        $this->assertFalse($updated->wasRecentlyCreated);
        $this->assertSame(1, DB::table('comments')->count());
        $this->assertSame(2, DB::table('comments')->value('user_id'));
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

    protected function createPost(): TestPost
    {
        return TestPost::create(['user_id' => 1, 'title' => 'Title', 'body' => 'Body']);
    }

    protected function assertMorphRow(TestPost $post): void
    {
        $row = DB::table('comments')->first();

        $this->assertSame(1, DB::table('comments')->count());
        $this->assertSame($post->id, $row->commentable_id);
        $this->assertSame(TestPost::class, $row->commentable_type);
        $this->assertSame('foo', $row->body);
        $this->assertSame(1, $row->user_id);
    }

    /**
     * Get a database connection instance.
     *
     * @return \Illuminate\Database\Connection
     */
    protected function connection()
    {
        return Eloquent::getConnectionResolver()->connection();
    }

    /**
     * Get a schema builder instance.
     *
     * @return \Illuminate\Database\Schema\Builder
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

    public function likes()
    {
        return $this->morphMany(TestLike::class, 'likeable');
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
