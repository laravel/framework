<?php

namespace Illuminate\Tests\Database;

use PHPUnit\Framework\TestCase;
use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model as Eloquent;

class PolymorphicDifferentPrimaryKeysIntegrationTest extends TestCase
{
    /**
     * Setup the database schema.
     *
     * @return void
     */
    public function createSchema()
    {
        $this->schema()->create('posts', function ($table) {
            $table->increments('post_id');
            $table->string('title');
            $table->string('body');
            $table->timestamps();
        });

        $this->schema()->create('videos', function ($table) {
            $table->increments('video_id');
            $table->string('title');
            $table->string('video');
            $table->timestamps();
        });

        $this->schema()->create('comments', function ($table) {
            $table->increments('id');
            $table->integer('commentable_id');
            $table->string('commentable_type');
            $table->text('body');
            $table->timestamps();
        });
    }

    protected function seedData()
    {
        $post1 = FooPost::create(['title' => 'Test Post', 'body' => 'hello world']);
        $post1->comments()->create(['body' => 'comment on a POST']);

        $video1 = FooVideo::create(['title' => 'Test Video', 'video' => 'my-video.mov']);
        $video1->comments()->create(['body' => 'comment on a VIDEO']);
    }

    // This test is OK
    public function testWithNoEagerOrLazyLoad()
    {
        $this->seedData();

        $comments = FooComment::all();

        $this->assertEquals(FooPost::first(), $comments->first()->commentable);
        $this->assertEquals(FooVideo::first(), $comments->last()->commentable);
    }

    // This test is OK
    public function testItCanEagerLoad()
    {
        $this->seedData();

        $comments = FooComment::with('commentable')->get();

        $this->assertEquals(FooPost::first(), $comments->first()->commentable);
        $this->assertEquals(FooVideo::first(), $comments->last()->commentable);
    }

    // This is the test that is failing
    public function testItCanLazyLoad()
    {
        $this->seedData();

        /*
         * This is the test that fails.
         * The query to lazy-load the Post is successful, but it assumes the
         * db key is the same as it was for the Video table, which it is not,
         * so the query fails.
         */

        $comments = FooComment::all();
        $comments->load('commentable');

        $this->assertEquals(FooPost::first(), $comments->first()->commentable);
        $this->assertEquals(FooVideo::first(), $comments->last()->commentable);
    }

    public function setUp()
    {
        $db = new DB;

        $db->addConnection([
            'driver'   => 'sqlite',
            'database' => ':memory:',
        ]);

        $db->bootEloquent();
        $db->setAsGlobal();

        $this->createSchema();
    }

    /**
     * Tear down the database schema.
     *
     * @return void
     */
    public function tearDown()
    {
        $this->schema()->drop('posts');
        $this->schema()->drop('videos');
        $this->schema()->drop('comments');
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
class FooPost extends Eloquent
{
    protected $table = 'posts';
    protected $primaryKey = 'post_id';
    protected $guarded = [];

    public function comments()
    {
        return $this->morphMany(FooComment::class, 'commentable');
    }
}

class FooVideo extends Eloquent
{
    protected $table = 'videos';
    protected $primaryKey = 'video_id';
    protected $guarded = [];

    public function comments()
    {
        return $this->morphMany(FooComment::class, 'commentable');
    }
}

/**
 * Eloquent Models...
 */
class FooComment extends Eloquent
{
    protected $table = 'comments';
    protected $guarded = [];

    public function commentable()
    {
        return $this->morphTo();
    }
}
