<?php

use Mockery as m;
use Illuminate\Database\Eloquent\Collection;
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
     * Unit test relation is properly initialized.
     */
    public function testRelationIsProperlyInitialized()
    {
        $relation = $this->getRelation();
        $model = m::mock('Illuminate\Database\Eloquent\Model');
        $model->shouldReceive('setRelation')->once()->with('foo', null);

        $models = $relation->initRelation([$model], 'foo');

        $this->assertEquals([$model], $models);
    }

    /**
     * Unit test eager constraints are properly added.
     */
    public function testEagerConstraintsAreProperlyAdded()
    {
        $relation = $this->getRelation();
        $relation->getQuery()->shouldReceive('whereIn')->once()->with('countries.id', [1, 2]);
        $model1 = new EloquentBelongsToThroughModelStub;
        $model1->id = 1;
        $model2 = new EloquentBelongsToThroughModelStub;
        $model2->id = 2;
        $relation->addEagerConstraints([$model1, $model2]);
    }

    /**
     * Unit test models are properly matched to their parents.
     */
    public function testModelsAreProperlyMatchedToParents()
    {
        $relation = $this->getRelation();

        // Countries
        $results1 = new EloquentBelongsToThroughModelStub;
        $results1->user_id = 1;
        $results2 = new EloquentBelongsToThroughModelStub;
        $results2->user_id = 2;

        // Posts
        $model1 = new EloquentBelongsToThroughModelStub;
        $model1->user_id = 1;
        $model2 = new EloquentBelongsToThroughModelStub;
        $model2->user_id = 2;

        $models = $relation->match([$model1, $model2], new Collection([$results1, $results2]), 'foo');

        $this->assertEquals(1, $models[0]->foo->user_id);
        $this->assertEquals(2, $models[1]->foo->user_id);
    }

    /**
     * Creates a new BelongsToThrough relationship of a Post having a Country through a User.
     *
     * @return BelongsToThrough
     */
    protected function getRelation()
    {
        list($builder, $country, $user, $farParentKey, $parentKey) = $this->getRelationArguments();

        return new BelongsToThrough($builder, $country, $user, $farParentKey, $parentKey);
    }

    protected function getRelationArguments()
    {
        $builder = m::mock('Illuminate\Database\Eloquent\Builder');
        $builder->shouldReceive('join')->once()->with('users', 'users.post_id', '=', 'posts.id');
        $builder->shouldReceive('join')->once()->with('countries', 'countries.user_id', '=', 'users.id');
        $builder->shouldReceive('where')->with('countries.id', '=', 1);
        $builder->shouldReceive('whereNotNull')->with('countries.id');

        $country = m::mock('Illuminate\Database\Eloquent\Model');
        $country->shouldReceive('getTable')->andReturn('countries');
        $country->shouldReceive('getQualifiedKeyName')->andReturn('countries.id');
        $country->shouldReceive('getKey')->andReturn(1);
        $country->shouldReceive('getKeyName')->andReturn('id');

        $user = m::mock('Illuminate\Database\Eloquent\Model');
        $user->shouldReceive('getTable')->andReturn('users');
        $user->shouldReceive('getQualifiedKeyName')->andReturn('users.id');

        $post = m::mock('Illuminate\Database\Eloquent\Model');
        $post->shouldReceive('getQualifiedKeyName')->andReturn('posts.id');

        $builder->shouldReceive('getModel')->andReturn($post);

        $user->shouldReceive('getKey')->andReturn(1);
        $user->shouldReceive('getCreatedAtColumn')->andReturn('created_at');
        $user->shouldReceive('getUpdatedAtColumn')->andReturn('updated_at');

        return [$builder, $country, $user, 'user_id', 'post_id'];
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

// Stub for unit tests
class EloquentBelongsToThroughModelStub extends Eloquent
{
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
