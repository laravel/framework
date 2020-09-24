<?php

namespace Illuminate\Tests\Database;

use Mockery;
use PHPUnit\Framework\TestCase;
use Illuminate\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Illuminate\Database\Eloquent\Factories\DisablesEvents;

class DatabaseEloquentFactoryDisablesEventsTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private const ALL_MODEL_EVENTS = [
        'retrieved' => 'dispatch',
        'creating' => 'until',
        'created' => 'dispatch',
        'updating' => 'until',
        'updated' => 'dispatch',
        'saving' => 'until',
        'saved' => 'dispatch',
        'deleting' => 'until',
        'deleted' => 'dispatch',
        'restoring' => 'until',
        'restored' => 'dispatch',
    ];

    private const CREATE_MODEL_EVENTS = [
        'creating' => 'until',
        'created' => 'dispatch',
        'saving' => 'until',
        'saved' => 'dispatch',
    ];

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
            $table->string('options')->nullable();
            $table->timestamps();
        });

        $this->schema()->create('posts', function ($table) {
            $table->increments('id');
            $table->foreignId('user_id');
            $table->string('title');
            $table->timestamps();
        });
    }

    public function test_factory_creates_without_events()
    {
        $dispatcher = Mockery::spy(Dispatcher::class);
        FactoryTestUser::setEventDispatcher($dispatcher);

        $user = FactoryTestUserFactory::new()->withoutEvents()->create();

        foreach (self::ALL_MODEL_EVENTS as $event => $method) {
            $dispatcher->shouldNotHaveReceived(
                $method,
                [
                    "eloquent.${event}: Illuminate\Tests\Database\FactoryTestUser",
                    Mockery::on(function ($arg) use ($user) {
                        return $arg->is($user);
                    }),
                ]
            );
        }

        $this->assertInstanceOf(FactoryTestUser::class, $user);
    }

    public function test_factory_creates_with_events()
    {
        $dispatcher = Mockery::spy(Dispatcher::class);
        FactoryTestUser::setEventDispatcher($dispatcher);

        $user = FactoryTestUserFactory::new()->create();

        foreach (self::CREATE_MODEL_EVENTS as $event => $method) {
            $dispatcher->shouldHaveReceived($method)
                ->with(
                    "eloquent.${event}: Illuminate\Tests\Database\FactoryTestUser",
                    Mockery::on(function ($arg) use ($user) {
                        return $arg->is($user);
                    }),
                );
        }

        $this->assertInstanceOf(FactoryTestUser::class, $user);
    }

    public function test_factory_creates_without_events_and_relationship()
    {
        $dispatcher = Mockery::spy(Dispatcher::class);
        FactoryTestPost::setEventDispatcher($dispatcher);
        FactoryTestUser::setEventDispatcher($dispatcher);

        $post = FactoryTestPostFactory::new()->withoutEvents()->create();

        foreach (self::ALL_MODEL_EVENTS as $event => $method) {
            $dispatcher->shouldNotHaveReceived(
                $method,
                [
                    "eloquent.${event}: Illuminate\Tests\Database\FactoryTestPost",
                    Mockery::type(FactoryTestPost::class),
                ]
            );
        }

        foreach (self::CREATE_MODEL_EVENTS as $event => $method) {
            $dispatcher->shouldHaveReceived($method)
                ->with(
                    "eloquent.${event}: Illuminate\Tests\Database\FactoryTestUser",
                    Mockery::type(FactoryTestUser::class)
                );
        }

        $this->assertInstanceOf(FactoryTestPost::class, $post);
        $this->assertInstanceOf(FactoryTestUser::class, $post->user);
    }

    public function test_factory_creates_with_events_and_relationship()
    {
        $dispatcher = Mockery::spy(Dispatcher::class);
        FactoryTestPost::setEventDispatcher($dispatcher);
        FactoryTestUser::setEventDispatcher($dispatcher);

        $post = FactoryTestPostFactory::new()->withEvents()->create();

        foreach (self::CREATE_MODEL_EVENTS as $event => $method) {
            $dispatcher->shouldHaveReceived($method)
                ->with(
                    "eloquent.${event}: Illuminate\Tests\Database\FactoryTestPost",
                    Mockery::type(FactoryTestPost::class)
                );
            $dispatcher->shouldHaveReceived($method)
                ->with(
                    "eloquent.${event}: Illuminate\Tests\Database\FactoryTestUser",
                    Mockery::type(FactoryTestUser::class)
                );
        }

        $this->assertInstanceOf(FactoryTestPost::class, $post);
        $this->assertInstanceOf(FactoryTestUser::class, $post->user);
    }

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
     * Tear down the database schema.
     *
     * @return void
     */
    protected function tearDown(): void
    {
        $this->schema()->drop('users');
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
    use DisablesEvents;

    protected $model = FactoryTestUser::class;

    public function definition()
    {
        return [
            'name' => $this->faker->name,
            'options' => null,
        ];
    }
}

class FactoryTestUser extends Eloquent
{
    use HasFactory;

    protected $table = 'users';

    public function posts()
    {
        return $this->hasMany(FactoryTestPost::class, 'user_id');
    }
}

class FactoryTestPostFactory extends Factory
{
    use DisablesEvents;

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

    public function author()
    {
        return $this->belongsTo(FactoryTestUser::class, 'user_id');
    }
}
