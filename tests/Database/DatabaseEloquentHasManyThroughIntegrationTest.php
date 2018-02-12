<?php

namespace Illuminate\Tests\Database;

use PHPUnit\Framework\TestCase;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model as Eloquent;

class DatabaseEloquentHasManyThroughIntegrationTest extends TestCase
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
            $table->unsignedInteger('country_id');
            $table->string('country_short');
            $table->timestamps();
            $table->softDeletes();
        });

        $this->schema()->create('posts', function ($table) {
            $table->increments('id');
            $table->integer('user_id');
            $table->string('title');
            $table->text('body');
            $table->string('email');
            $table->timestamps();
        });

        $this->schema()->create('countries', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->string('shortname');
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
        $this->schema()->drop('countries');
    }

    public function testItLoadsAHasManyThroughRelationWithCustomKeys()
    {
        $this->seedData();
        $posts = HasManyThroughTestCountry::first()->posts;

        $this->assertEquals('A title', $posts[0]->title);
        $this->assertCount(2, $posts);
    }

    public function testItLoadsADefaultHasManyThroughRelation()
    {
        $this->migrateDefault();
        $this->seedDefaultData();

        $posts = HasManyThroughDefaultTestCountry::first()->posts;
        $this->assertEquals('A title', $posts[0]->title);
        $this->assertCount(2, $posts);

        $this->resetDefault();
    }

    public function testItLoadsARelationWithCustomIntermediateAndLocalKey()
    {
        $this->seedData();
        $posts = HasManyThroughIntermediateTestCountry::first()->posts;

        $this->assertEquals('A title', $posts[0]->title);
        $this->assertCount(2, $posts);
    }

    public function testEagerLoadingARelationWithCustomIntermediateAndLocalKey()
    {
        $this->seedData();
        $posts = HasManyThroughIntermediateTestCountry::with('posts')->first()->posts;

        $this->assertEquals('A title', $posts[0]->title);
        $this->assertCount(2, $posts);
    }

    public function testWhereHasOnARelationWithCustomIntermediateAndLocalKey()
    {
        $this->seedData();
        $country = HasManyThroughIntermediateTestCountry::whereHas('posts', function ($query) {
            $query->where('title', 'A title');
        })->get();

        $this->assertCount(1, $country);
    }

    /**
     * @expectedException \Illuminate\Database\Eloquent\ModelNotFoundException
     * @expectedExceptionMessage No query results for model [Illuminate\Tests\Database\HasManyThroughTestPost].
     */
    public function testFirstOrFailThrowsAnException()
    {
        HasManyThroughTestCountry::create(['id' => 1, 'name' => 'United States of America', 'shortname' => 'us'])
            ->users()->create(['id' => 1, 'email' => 'taylorotwell@gmail.com', 'country_short' => 'us']);

        HasManyThroughTestCountry::first()->posts()->firstOrFail();
    }

    /**
     * @expectedException \Illuminate\Database\Eloquent\ModelNotFoundException
     * @expectedExceptionMessage No query results for model [Illuminate\Tests\Database\HasManyThroughTestPost].
     */
    public function testFindOrFailThrowsAnException()
    {
        HasManyThroughTestCountry::create(['id' => 1, 'name' => 'United States of America', 'shortname' => 'us'])
                                 ->users()->create(['id' => 1, 'email' => 'taylorotwell@gmail.com', 'country_short' => 'us']);

        HasManyThroughTestCountry::first()->posts()->findOrFail(1);
    }

    public function testFirstRetrievesFirstRecord()
    {
        $this->seedData();
        $post = HasManyThroughTestCountry::first()->posts()->first();

        $this->assertNotNull($post);
        $this->assertEquals('A title', $post->title);
    }

    public function testAllColumnsAreRetrievedByDefault()
    {
        $this->seedData();
        $post = HasManyThroughTestCountry::first()->posts()->first();

        $this->assertEquals([
            'id',
            'user_id',
            'title',
            'body',
            'email',
            'created_at',
            'updated_at',
            'country_id',
        ], array_keys($post->getAttributes()));
    }

    public function testOnlyProperColumnsAreSelectedIfProvided()
    {
        $this->seedData();
        $post = HasManyThroughTestCountry::first()->posts()->first(['title', 'body']);

        $this->assertEquals([
            'title',
            'body',
            'country_id',
        ], array_keys($post->getAttributes()));
    }

    public function testIntermediateSoftDeletesAreIgnored()
    {
        $this->seedData();
        HasManyThroughSoftDeletesTestUser::first()->delete();

        $posts = HasManyThroughSoftDeletesTestCountry::first()->posts;

        $this->assertEquals('A title', $posts[0]->title);
        $this->assertCount(2, $posts);
    }

    public function testEagerLoadingLoadsRelatedModelsCorrectly()
    {
        $this->seedData();
        $country = HasManyThroughSoftDeletesTestCountry::with('posts')->first();

        $this->assertEquals('us', $country->shortname);
        $this->assertEquals('A title', $country->posts[0]->title);
        $this->assertCount(2, $country->posts);
    }

    /**
     * Helpers...
     */
    protected function seedData()
    {
        HasManyThroughTestCountry::create(['id' => 1, 'name' => 'United States of America', 'shortname' => 'us'])
                                 ->users()->create(['id' => 1, 'email' => 'taylorotwell@gmail.com', 'country_short' => 'us'])
                                 ->posts()->createMany([
                ['title' => 'A title', 'body' => 'A body', 'email' => 'taylorotwell@gmail.com'],
                ['title' => 'Another title', 'body' => 'Another body', 'email' => 'taylorotwell@gmail.com'],
            ]);
    }

    /**
     * Seed data for a default HasManyThrough setup.
     */
    protected function seedDefaultData()
    {
        HasManyThroughDefaultTestCountry::create(['id' => 1, 'name' => 'United States of America'])
                                 ->users()->create(['id' => 1, 'email' => 'taylorotwell@gmail.com'])
                                 ->posts()->createMany([
                ['title' => 'A title', 'body' => 'A body'],
                ['title' => 'Another title', 'body' => 'Another body'],
            ]);
    }

    /**
     * Drop the default tables.
     */
    protected function resetDefault()
    {
        $this->schema()->drop('users_default');
        $this->schema()->drop('posts_default');
        $this->schema()->drop('countries_default');
    }

    /**
     * Migrate tables for classes with a Laravel "default" HasManyThrough setup.
     */
    protected function migrateDefault()
    {
        $this->schema()->create('users_default', function ($table) {
            $table->increments('id');
            $table->string('email')->unique();
            $table->unsignedInteger('has_many_through_default_test_country_id');
            $table->timestamps();
        });

        $this->schema()->create('posts_default', function ($table) {
            $table->increments('id');
            $table->integer('has_many_through_default_test_user_id');
            $table->string('title');
            $table->text('body');
            $table->timestamps();
        });

        $this->schema()->create('countries_default', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });
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
class HasManyThroughTestUser extends Eloquent
{
    protected $table = 'users';
    protected $guarded = [];

    public function posts()
    {
        return $this->hasMany(HasManyThroughTestPost::class, 'user_id');
    }
}

/**
 * Eloquent Models...
 */
class HasManyThroughTestPost extends Eloquent
{
    protected $table = 'posts';
    protected $guarded = [];

    public function owner()
    {
        return $this->belongsTo(HasManyThroughTestUser::class, 'user_id');
    }
}

class HasManyThroughTestCountry extends Eloquent
{
    protected $table = 'countries';
    protected $guarded = [];

    public function posts()
    {
        return $this->hasManyThrough(HasManyThroughTestPost::class, HasManyThroughTestUser::class, 'country_id', 'user_id');
    }

    public function users()
    {
        return $this->hasMany(HasManyThroughTestUser::class, 'country_id');
    }
}

/**
 * Eloquent Models...
 */
class HasManyThroughDefaultTestUser extends Eloquent
{
    protected $table = 'users_default';
    protected $guarded = [];

    public function posts()
    {
        return $this->hasMany(HasManyThroughDefaultTestPost::class);
    }
}

/**
 * Eloquent Models...
 */
class HasManyThroughDefaultTestPost extends Eloquent
{
    protected $table = 'posts_default';
    protected $guarded = [];

    public function owner()
    {
        return $this->belongsTo(HasManyThroughDefaultTestUser::class);
    }
}

class HasManyThroughDefaultTestCountry extends Eloquent
{
    protected $table = 'countries_default';
    protected $guarded = [];

    public function posts()
    {
        return $this->hasManyThrough(HasManyThroughDefaultTestPost::class, HasManyThroughDefaultTestUser::class);
    }

    public function users()
    {
        return $this->hasMany(HasManyThroughDefaultTestUser::class);
    }
}

class HasManyThroughIntermediateTestCountry extends Eloquent
{
    protected $table = 'countries';
    protected $guarded = [];

    public function posts()
    {
        return $this->hasManyThrough(HasManyThroughTestPost::class, HasManyThroughTestUser::class, 'country_short', 'email', 'shortname', 'email');
    }

    public function users()
    {
        return $this->hasMany(HasManyThroughTestUser::class, 'country_id');
    }
}

class HasManyThroughSoftDeletesTestUser extends Eloquent
{
    use SoftDeletes;

    protected $table = 'users';
    protected $guarded = [];

    public function posts()
    {
        return $this->hasMany(HasManyThroughSoftDeletesTestPost::class, 'user_id');
    }
}

/**
 * Eloquent Models...
 */
class HasManyThroughSoftDeletesTestPost extends Eloquent
{
    protected $table = 'posts';
    protected $guarded = [];

    public function owner()
    {
        return $this->belongsTo(HasManyThroughSoftDeletesTestUser::class, 'user_id');
    }
}

class HasManyThroughSoftDeletesTestCountry extends Eloquent
{
    protected $table = 'countries';
    protected $guarded = [];

    public function posts()
    {
        return $this->hasManyThrough(HasManyThroughSoftDeletesTestPost::class, HasManyThroughTestUser::class, 'country_id', 'user_id');
    }

    public function users()
    {
        return $this->hasMany(HasManyThroughSoftDeletesTestUser::class, 'country_id');
    }
}
