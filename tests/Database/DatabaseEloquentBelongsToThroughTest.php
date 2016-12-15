<?php

use Mockery as m;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Relations\BelongsToThrough;

class DatabaseEloquentBelongsToThroughTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $db = new DB();

        $db->addConnection([
            'driver'    => 'sqlite',
            'database'  => ':memory:',
        ]);

        $db->bootEloquent();
        $db->setAsGlobal();

        $this->createSchema();
        $this->populateDb();
    }

    protected function createSchema()
    {
        $this->schema()->create('posts', function ($table) {
            $table->increments('id');
            $table->string('title');
            $table->integer('eloquent_belongs_to_through_model_user_id');
            $table->timestamps();
        });

        $this->schema()->create('users', function ($table) {
            $table->increments('id');
            $table->string('email');
            $table->integer('eloquent_belongs_to_through_model_country_id');
            $table->timestamps();
        });

        $this->schema()->create('countries', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });
    }

    protected function populateDb()
    {
        // Insert countries
        $this->connection()->insert('insert into countries (name) values (\'FooBar\'), (\'BarFoo\')');

        // Insert users
        $this->connection()->insert(
            'insert into users (email, eloquent_belongs_to_through_model_country_id) values (\'foobar@example.com\', ?), (\'barfoo@example.com\', ?)',
            $this->object_array_column($this->connection()->select('select id from countries'), 'id')
        );

        // Insert posts
        $this->connection()->insert(
            'insert into posts (title, eloquent_belongs_to_through_model_user_id) values (\'FooBar\', ?), (\'BarFoo\', ?)',
            $this->object_array_column($this->connection()->select('select id from users'), 'id')
        );
    }

    public function tearDown()
    {
        foreach (['default'] as $connection) {
            $this->schema($connection)->drop('posts');
            $this->schema($connection)->drop('users');
            $this->schema($connection)->drop('countries');
        }

        m::close();
    }

    /**
     * Integration test for lazy loading of the $post's $country.
     */
    public function testIntegrationRetrieveLazy()
    {
        $post = EloquentBelongsToThroughModelPost::where('title', 'BarFoo')->first();

        $country = $post->country;

        $this->assertNotNull($country);
        $this->assertInstanceOf('EloquentBelongsToThroughModelCountry', $country);
        $this->assertEquals('BarFoo', $country->name);
    }

    /**
     * Integration test for eager loading of the $post's $country.
     */
    public function testIntegrationRetrieveEager()
    {
        $post = EloquentBelongsToThroughModelPost::where('title', 'FooBar')->with('country')->first();

        $this->assertNotNull($post->country);
        $this->assertInstanceOf('EloquentBelongsToThroughModelCountry', $post->country);
        $this->assertEquals('FooBar', $post->country->name);
    }

    /**
     * Integration test for multiple eager loading of the $post's $country.
     */
    public function testIntegrationRetrieveEagerMultiple()
    {
        $posts = EloquentBelongsToThroughModelPost::with('country')->get();

        $this->assertInstanceOf('\Illuminate\Database\Eloquent\Collection', $posts);
        foreach ($posts as $post) {
            $this->assertNotNull($post->country);
            $this->assertInstanceOf('EloquentBelongsToThroughModelCountry', $post->country);
            $this->assertEquals($post->title, $post->country->name);
        }
    }

    /**
     * Helpers...
     */

    /**
     * Get a database connection instance.
     *
     * @return \Illuminate\Database\Connection
     */
    protected function connection($connection = 'default')
    {
        return Eloquent::getConnectionResolver()->connection($connection);
    }

    /**
     * Get a schema builder instance.
     *
     * @return \Illuminate\Database\Schema\Builder
     */
    protected function schema($connection = 'default')
    {
        return $this->connection($connection)->getSchemaBuilder();
    }

    /**
     * @param array  $array array of stdClass
     * @param string $property
     *
     * @return array
     */
    protected function object_array_column($array, $property)
    {
        $columns = [];
        foreach ($array as $item) {
            $columns[] = $item->{$property};
        }

        return $columns;
    }
}

// Stubs for integration tests
class EloquentBelongsToThroughModelPost extends Eloquent
{
    protected $table = 'posts';

    public function country()
    {
        return $this->belongsToThrough('EloquentBelongsToThroughModelCountry', 'EloquentBelongsToThroughModelUser');
    }
}
class EloquentBelongsToThroughModelUser extends Eloquent
{
    protected $table = 'users';
}
class EloquentBelongsToThroughModelCountry extends Eloquent
{
    protected $table = 'countries';
}
