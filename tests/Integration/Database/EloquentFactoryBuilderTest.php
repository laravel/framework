<?php

namespace Illuminate\Tests\Integration\Database;

use Faker\Generator;
use Orchestra\Testbench\TestCase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factory;

/**
 * @group integration
 */
class EloquentFactoryBuilderTest extends TestCase
{
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('app.debug', 'true');

        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $app['config']->set('database.connections.alternative-connection', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $factory = new Factory($app->make(Generator::class));

        $factory->define(FactoryBuildableUser::class, function (Generator $faker) {
            return [
                'name' => $faker->name,
                'email' => $faker->unique()->safeEmail,
            ];
        });

        $factory->define(FactoryBuildableServer::class, function (Generator $faker) {
            return [
                'name' => $faker->name,
                'status' => 'active',
                'user_id' => function () {
                    return factory(FactoryBuildableUser::class)->create()->id;
                },
            ];
        });

        $factory->state(FactoryBuildableServer::class, 'callable', function (Generator $faker) {
            return [
                'status' => 'callable',
            ];
        });

        $factory->state(FactoryBuildableServer::class, 'inline', ['status' => 'inline']);

        $app->singleton(Factory::class, function ($app) use ($factory) {
            return $factory;
        });
    }

    public function setUp()
    {
        parent::setUp();

        Schema::create('users', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->string('email');
        });

        Schema::connection('alternative-connection')->create('users', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->string('email');
        });

        Schema::create('servers', function ($table) {
            $table->increments('id');
            $table->string('name');
            $table->integer('user_id');
            $table->string('status');
        });
    }

    /**
     * @test
     */
    public function creating_factory_models()
    {
        $user = factory(FactoryBuildableUser::class)->create();

        $dbUser = FactoryBuildableUser::find(1);

        $this->assertTrue($user->is($dbUser));
    }

    /**
     * @test
     */
    public function creating_factory_models_overriding_attributes()
    {
        $user = factory(FactoryBuildableUser::class)->create(['name' => 'Zain']);

        $this->assertEquals('Zain', $user->name);
    }

    /**
     * @test
     */
    public function creating_collection_of_models()
    {
        $users = factory(FactoryBuildableUser::class, 3)->create();

        $instances = factory(FactoryBuildableUser::class, 3)->make();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $users);
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $instances);
        $this->assertCount(3, $users);
        $this->assertCount(3, $instances);
        $this->assertCount(3, FactoryBuildableUser::find($users->pluck('id')->toArray()));
        $this->assertCount(0, FactoryBuildableUser::find($instances->pluck('id')->toArray()));
    }

    /**
     * @test
     */
    public function creating_models_with_callable_states()
    {
        $server = factory(FactoryBuildableServer::class)->create();

        $callableServer = factory(FactoryBuildableServer::class)->states('callable')->create();

        $this->assertEquals('active', $server->status);
        $this->assertEquals('callable', $callableServer->status);
    }

    /**
     * @test
     */
    public function creating_models_with_inline_states()
    {
        $server = factory(FactoryBuildableServer::class)->create();

        $inlineServer = factory(FactoryBuildableServer::class)->states('inline')->create();

        $this->assertEquals('active', $server->status);
        $this->assertEquals('inline', $inlineServer->status);
    }

    /**
     * @test
     */
    public function creating_models_with_relationships()
    {
        factory(FactoryBuildableUser::class, 2)
            ->create()
            ->each(function ($user) {
                $user->servers()->saveMany(factory(FactoryBuildableServer::class, 2)->make());
            })
            ->each(function ($user) {
                $this->assertCount(2, $user->servers);
            });
    }

    /**
     * @test
     */
    public function creating_models_on_custom_connection()
    {
        $user = factory(FactoryBuildableUser::class)
            ->connection('alternative-connection')
            ->create();

        $dbUser = FactoryBuildableUser::on('alternative-connection')->find(1);

        $this->assertEquals('alternative-connection', $user->getConnectionName());
        $this->assertTrue($user->is($dbUser));
    }

    /** @test */
    public function making_models_with_a_custom_connection()
    {
        $user = factory(FactoryBuildableUser::class)
            ->connection('alternative-connection')
            ->make();

        $this->assertEquals('alternative-connection', $user->getConnectionName());
    }
}

class FactoryBuildableUser extends Model
{
    public $table = 'users';
    public $timestamps = false;
    protected $guarded = ['id'];

    public function servers()
    {
        return $this->hasMany(FactoryBuildableServer::class, 'user_id');
    }
}

class FactoryBuildableServer extends Model
{
    public $table = 'servers';
    public $timestamps = false;
    protected $guarded = ['id'];

    public function user()
    {
        return $this->belongsTo(FactoryBuildableUser::class, 'user_id');
    }
}
