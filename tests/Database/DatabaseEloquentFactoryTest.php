<?php

namespace Illuminate\Tests\Database;

use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model as Eloquent;
use PHPUnit\Framework\TestCase;

class DatabaseEloquentFactoryTest extends TestCase
{
    protected function setUp(): void
    {
        Container::getInstance()->singleton(\Faker\Generator::class, function ($app, $parameters) {
            return \Faker\Factory::create('en_US');
        });

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
            $table->string('name');
            $table->timestamps();
        });

        $this->schema()->create('posts', function ($table) {
            $table->increments('id');
            $table->foreignId('user_id');
            $table->string('title');
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
    }

    public function test_basic_model_can_be_created()
    {
        $user = FactoryTestUserFactory::new()->create();
        $this->assertInstanceOf(Eloquent::class, $user);

        $user = FactoryTestUserFactory::new()->create(['name' => 'Taylor Otwell']);
        $this->assertInstanceOf(Eloquent::class, $user);
        $this->assertEquals('Taylor Otwell', $user->name);

        $users = FactoryTestUserFactory::times(10)->create();
        $this->assertCount(10, $users);
    }

    public function test_make_creates_unpersisted_model_instance()
    {
        $user = FactoryTestUserFactory::new()->make(['name' => 'Taylor Otwell']);

        $this->assertInstanceOf(Eloquent::class, $user);
        $this->assertEquals('Taylor Otwell', $user->name);
        $this->assertCount(0, FactoryTestUser::all());
    }

    public function test_after_creating_and_making_callbacks_are_called()
    {
        $user = FactoryTestUserFactory::new()
                        ->afterMaking(function ($user) {
                            $_SERVER['__test.user.making'] = $user;
                        })
                        ->afterCreating(function ($user) {
                            $_SERVER['__test.user.creating'] = $user;
                        })
                        ->create();

        $this->assertSame($user, $_SERVER['__test.user.making']);
        $this->assertSame($user, $_SERVER['__test.user.creating']);

        unset($_SERVER['__test.user.making']);
        unset($_SERVER['__test.user.creating']);
    }

    public function test_has_many_relationship()
    {
        $users = FactoryTestUserFactory::times(10)
                        ->has(
                            FactoryTestPostFactory::times(3)
                                    // Test parents passed to callback...
                                    ->afterCreating(function ($post, $user) {
                                        $_SERVER['__test.post.creating-post'] = $post;
                                        $_SERVER['__test.post.creating-user'] = $user;
                                    }),
                            'posts'
                        )
                        ->create();

        $this->assertCount(10, FactoryTestUser::all());
        $this->assertCount(30, FactoryTestPost::all());
        $this->assertCount(3, FactoryTestUser::latest()->first()->posts);

        $this->assertInstanceOf(Eloquent::class, $_SERVER['__test.post.creating-post']);
        $this->assertInstanceOf(Eloquent::class, $_SERVER['__test.post.creating-user']);

        unset($_SERVER['__test.post.creating-post']);
        unset($_SERVER['__test.post.creating-user']);
    }

    public function test_belongs_to_relationship()
    {
        $posts = FactoryTestPostFactory::times(3)
                        ->for(FactoryTestUserFactory::new(['name' => 'Taylor Otwell']), 'user')
                        ->create();

        $this->assertCount(3, $posts->filter(function ($post) {
            return $post->user->name == 'Taylor Otwell';
        }));

        $this->assertCount(1, FactoryTestUser::all());
        $this->assertCount(3, FactoryTestPost::all());
    }

    /**
     * Get a database connection instance.
     *
     * @return \Illuminate\Database\ConnectionInterface
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

class FactoryTestUserFactory extends Factory
{
    protected $model = FactoryTestUser::class;

    public function definition()
    {
        return [
            'name' => $this->faker->name,
        ];
    }
}

class FactoryTestUser extends Eloquent
{
    protected $table = 'users';

    public function posts()
    {
        return $this->hasMany(FactoryTestPost::class, 'user_id');
    }
}

class FactoryTestPostFactory extends Factory
{
    protected $model = FactoryTestPost::class;

    public function definition()
    {
        return [
            'user_id' => FactoryTestUserFactory::new(),
            'title' => $this->faker->name,
        ];
    }
}

class FactoryTestPost extends Eloquent
{
    protected $table = 'posts';

    public function user()
    {
        return $this->belongsTo(FactoryTestUser::class, 'user_id');
    }
}
