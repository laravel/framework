<?php

namespace Illuminate\Tests\Database;

use Illuminate\Container\Container;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Factories\DisablesEvents;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

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
        FactoryEventTestUser::setEventDispatcher($dispatcher);

        $user = FactoryEventTestUserFactory::new()->withoutEvents()->create();

        foreach (self::ALL_MODEL_EVENTS as $event => $method) {
            $dispatcher->shouldNotHaveReceived(
                $method,
                [
                    "eloquent.${event}: Illuminate\Tests\Database\FactoryEventTestUser",
                    Mockery::on(function ($arg) use ($user) {
                        return $arg->is($user);
                    }),
                ]
            );
        }

        $this->assertInstanceOf(FactoryEventTestUser::class, $user);
    }

    public function test_factory_creates_with_events()
    {
        $dispatcher = Mockery::spy(Dispatcher::class);
        FactoryEventTestUser::setEventDispatcher($dispatcher);

        $user = FactoryEventTestUserFactory::new()->create();

        foreach (self::CREATE_MODEL_EVENTS as $event => $method) {
            $dispatcher->shouldHaveReceived($method)
                ->with(
                    "eloquent.${event}: Illuminate\Tests\Database\FactoryEventTestUser",
                    Mockery::on(function ($arg) use ($user) {
                        return $arg->is($user);
                    }),
                );
        }

        $this->assertInstanceOf(FactoryEventTestUser::class, $user);
    }

    public function test_factory_creates_without_events_and_relationship()
    {
        $dispatcher = Mockery::spy(Dispatcher::class);
        FactoryEventTestPost::setEventDispatcher($dispatcher);
        FactoryEventTestUser::setEventDispatcher($dispatcher);

        $post = FactoryEventTestPostFactory::new()->withoutEvents()->create();

        foreach (self::ALL_MODEL_EVENTS as $event => $method) {
            $dispatcher->shouldNotHaveReceived(
                $method,
                [
                    "eloquent.${event}: Illuminate\Tests\Database\FactoryEventTestPost",
                    Mockery::type(FactoryEventTestPost::class),
                ]
            );
        }

        foreach (self::CREATE_MODEL_EVENTS as $event => $method) {
            $dispatcher->shouldHaveReceived($method)
                ->with(
                    "eloquent.${event}: Illuminate\Tests\Database\FactoryEventTestUser",
                    Mockery::type(FactoryEventTestUser::class)
                );
        }

        $this->assertInstanceOf(FactoryEventTestPost::class, $post);
        $this->assertInstanceOf(FactoryEventTestUser::class, $post->user);
    }

    public function test_factory_creates_with_events_and_relationship()
    {
        $dispatcher = Mockery::spy(Dispatcher::class);
        FactoryEventTestPost::setEventDispatcher($dispatcher);
        FactoryEventTestUser::setEventDispatcher($dispatcher);

        $post = FactoryEventTestPostFactory::new()->withEvents()->create();

        foreach (self::CREATE_MODEL_EVENTS as $event => $method) {
            $dispatcher->shouldHaveReceived($method)
                ->with(
                    "eloquent.${event}: Illuminate\Tests\Database\FactoryEventTestPost",
                    Mockery::type(FactoryEventTestPost::class)
                );
            $dispatcher->shouldHaveReceived($method)
                ->with(
                    "eloquent.${event}: Illuminate\Tests\Database\FactoryEventTestUser",
                    Mockery::type(FactoryEventTestUser::class)
                );
        }

        $this->assertInstanceOf(FactoryEventTestPost::class, $post);
        $this->assertInstanceOf(FactoryEventTestUser::class, $post->user);
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

class FactoryEventTestUserFactory extends Factory
{
    use DisablesEvents;

    protected $model = FactoryEventTestUser::class;

    public function definition()
    {
        return [
            'name' => $this->faker->name,
            'options' => null,
        ];
    }
}

class FactoryEventTestUser extends Eloquent
{
    use HasFactory;

    protected $table = 'users';

    public function posts()
    {
        return $this->hasMany(FactoryEventTestPost::class, 'user_id');
    }
}

class FactoryEventTestPostFactory extends Factory
{
    use DisablesEvents;

    protected $model = FactoryEventTestPost::class;

    public function definition()
    {
        return [
            'user_id' => FactoryEventTestUserFactory::new(),
            'title' => $this->faker->name,
        ];
    }
}

class FactoryEventTestPost extends Eloquent
{
    protected $table = 'posts';

    public function user()
    {
        return $this->belongsTo(FactoryEventTestUser::class, 'user_id');
    }

    public function author()
    {
        return $this->belongsTo(FactoryEventTestUser::class, 'user_id');
    }
}
