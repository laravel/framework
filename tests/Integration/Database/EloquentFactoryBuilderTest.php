<?php

namespace Illuminate\Tests\Integration\Database;

use Faker\Generator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase;

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

        $factory->define(FactoryBuildableProfile::class, function (Generator $faker) {
            return [
                'user_id' => function () {
                    return factory(FactoryBuildableUser::class)->create()->id;
                },
            ];
        });

        $factory->afterMaking(FactoryBuildableUser::class, function (FactoryBuildableUser $user, Generator $faker) {
            $profile = factory(FactoryBuildableProfile::class)->make(['user_id' => $user->id]);
            $user->setRelation('profile', $profile);
        });

        $factory->afterMakingState(FactoryBuildableUser::class, 'with_callable_server', function (FactoryBuildableUser $user, Generator $faker) {
            $server = factory(FactoryBuildableServer::class)
                ->state('callable')
                ->make(['user_id' => $user->id]);

            $user->servers->push($server);
        });

        $factory->define(FactoryBuildableTeam::class, function (Generator $faker) {
            return [
                'name' => $faker->name,
                'owner_id' => function () {
                    return factory(FactoryBuildableUser::class)->create()->id;
                },
            ];
        });

        $factory->afterCreating(FactoryBuildableTeam::class, function (FactoryBuildableTeam $team, Generator $faker) {
            $team->users()->attach($team->owner);
        });

        $factory->define(FactoryBuildableServer::class, function (Generator $faker) {
            return [
                'name' => $faker->name,
                'status' => 'active',
                'tags' => ['Storage', 'Data'],
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

        $factory->afterCreatingState(FactoryBuildableUser::class, 'with_callable_server', function (FactoryBuildableUser $user, Generator $faker) {
            factory(FactoryBuildableServer::class)
                ->state('callable')
                ->create(['user_id' => $user->id]);
        });

        $factory->state(FactoryBuildableServer::class, 'inline', ['status' => 'inline']);

        $app->singleton(Factory::class, function ($app) use ($factory) {
            return $factory;
        });
    }

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('email');
        });

        Schema::create('profiles', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');
        });

        Schema::create('teams', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('owner_id');
        });

        Schema::create('team_users', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('team_id');
            $table->unsignedInteger('user_id');
        });

        Schema::connection('alternative-connection')->create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('email');
        });

        Schema::create('servers', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('tags');
            $table->integer('user_id');
            $table->string('status');
        });
    }

    public function testCreatingFactoryModels()
    {
        $user = factory(FactoryBuildableUser::class)->create();

        $dbUser = FactoryBuildableUser::find(1);

        $this->assertTrue($user->is($dbUser));
    }

    public function testCreatingFactoryModelsOverridingAttributes()
    {
        $user = factory(FactoryBuildableUser::class)->create(['name' => 'Zain']);

        $this->assertSame('Zain', $user->name);
    }

    public function testCreatingCollectionOfModels()
    {
        $users = factory(FactoryBuildableUser::class, 3)->create();

        $instances = factory(FactoryBuildableUser::class, 3)->make();

        $this->assertInstanceOf(Collection::class, $users);
        $this->assertInstanceOf(Collection::class, $instances);
        $this->assertCount(3, $users);
        $this->assertCount(3, $instances);
        $this->assertCount(3, FactoryBuildableUser::find($users->pluck('id')->toArray()));
        $this->assertCount(0, FactoryBuildableUser::find($instances->pluck('id')->toArray()));
    }

    public function testCreateManyCollectionOfModels()
    {
        $users = factory(FactoryBuildableUser::class)->createMany([
            [
                'name' => 'Taylor',
            ],
            [
                'name' => 'John',
            ],
            [
                'name' => 'Doe',
            ],
        ]);
        $this->assertInstanceOf(Collection::class, $users);
        $this->assertCount(3, $users);
        $this->assertCount(3, FactoryBuildableUser::find($users->pluck('id')->toArray()));
        $this->assertEquals(['Taylor', 'John', 'Doe'], $users->pluck('name')->toArray());
    }

    public function testCreatingModelsWithCallableState()
    {
        $server = factory(FactoryBuildableServer::class)->create();

        $callableServer = factory(FactoryBuildableServer::class)->state('callable')->create();

        $this->assertSame('active', $server->status);
        $this->assertEquals(['Storage', 'Data'], $server->tags);
        $this->assertSame('callable', $callableServer->status);
    }

    public function testCreatingModelsWithInlineState()
    {
        $server = factory(FactoryBuildableServer::class)->create();

        $inlineServer = factory(FactoryBuildableServer::class)->state('inline')->create();

        $this->assertSame('active', $server->status);
        $this->assertSame('inline', $inlineServer->status);
    }

    public function testCreatingModelsWithRelationships()
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

    public function testCreatingModelsOnCustomConnection()
    {
        $user = factory(FactoryBuildableUser::class)
            ->connection('alternative-connection')
            ->create();

        $dbUser = FactoryBuildableUser::on('alternative-connection')->find(1);

        $this->assertSame('alternative-connection', $user->getConnectionName());
        $this->assertTrue($user->is($dbUser));
    }

    public function testCreatingModelsWithAfterCallback()
    {
        $team = factory(FactoryBuildableTeam::class)->create();

        $this->assertTrue($team->users->contains($team->owner));
    }

    public function testCreatingModelsWithAfterCallbackState()
    {
        $user = factory(FactoryBuildableUser::class)->state('with_callable_server')->create();

        $this->assertNotNull($user->profile);
        $this->assertNotNull($user->servers->where('status', 'callable')->first());
    }

    public function testMakingModelsWithACustomConnection()
    {
        $user = factory(FactoryBuildableUser::class)
            ->connection('alternative-connection')
            ->make();

        $this->assertSame('alternative-connection', $user->getConnectionName());
    }

    public function testMakingModelsWithAfterCallback()
    {
        $user = factory(FactoryBuildableUser::class)->make();

        $this->assertNotNull($user->profile);
    }

    public function testMakingModelsWithAfterCallbackState()
    {
        $user = factory(FactoryBuildableUser::class)->state('with_callable_server')->make();

        $this->assertNotNull($user->profile);
        $this->assertNotNull($user->servers->where('status', 'callable')->first());
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

    public function profile()
    {
        return $this->hasOne(FactoryBuildableProfile::class, 'user_id');
    }
}

class FactoryBuildableProfile extends Model
{
    public $table = 'profiles';
    public $timestamps = false;
    protected $guarded = ['id'];

    public function user()
    {
        return $this->belongsTo(FactoryBuildableUser::class, 'user_id');
    }
}

class FactoryBuildableTeam extends Model
{
    public $table = 'teams';
    public $timestamps = false;
    protected $guarded = ['id'];

    public function owner()
    {
        return $this->belongsTo(FactoryBuildableUser::class, 'owner_id');
    }

    public function users()
    {
        return $this->belongsToMany(
            FactoryBuildableUser::class,
            'team_users',
            'team_id',
            'user_id'
        );
    }
}

class FactoryBuildableServer extends Model
{
    public $table = 'servers';
    public $timestamps = false;
    protected $guarded = ['id'];
    public $casts = ['tags' => 'array'];

    public function user()
    {
        return $this->belongsTo(FactoryBuildableUser::class, 'user_id');
    }
}
