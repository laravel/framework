<?php

namespace Illuminate\Tests\Integration\Routing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Transactional;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Tests\Integration\Database\DatabaseTestCase;

class TransactionalTargetTest extends DatabaseTestCase
{
    protected function afterRefreshingDatabase()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });

        Schema::connection('second_connection')->create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });
    }

    protected function defineEnvironment($app)
    {
        parent::defineEnvironment($app);

        $app['config']->set([
            'database.connections.second_connection' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
            ],
        ]);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        TransactionalTestController::$shouldFail = false;
    }

    public function testItExecutesAMethodInsideATransaction()
    {
        Route::get('user', [TransactionalTestController::class, 'store']);

        $this->get('user');

        $this->assertDatabaseHas('users', ['name' => 'Mateus']);
    }

    public function testItRollbacksChangesUponFailure()
    {
        TransactionalTestController::$shouldFail = true;
        Route::get('user', [TransactionalTestController::class, 'store']);

        $this->get('user')->assertStatus(500);
        $this->assertDatabaseMissing('users', ['name' => 'Mateus']);
    }

    public function testItRollbacksChangesUponFailureInvokableController()
    {
        TransactionalTestController::$shouldFail = true;
        Route::get('user', TransactionalTestController::class);

        $this->get('user')->assertStatus(500);
        $this->assertDatabaseMissing('users', ['name' => 'Mateus']);
    }

    public function testItExecutesAMethodInsideATransactionSecondConnection()
    {
        Route::get('user', [TransactionalTestControllerSecondConnection::class, 'store']);

        $this->get('user');

        $this->assertDatabaseHas('users', ['name' => 'Mateus'], 'second_connection');
    }

    public function testItRollbacksChangesUponFailureDifferentConnection()
    {
        TransactionalTestControllerSecondConnection::$shouldFail = true;
        Route::get('user', [TransactionalTestControllerSecondConnection::class, 'store']);

        $this->get('user')->assertStatus(500);
        $this->assertDatabaseMissing('users', ['name' => 'Mateus'], 'second_connection');
    }
}

class TransactionalTestController
{
    public static $shouldFail = false;

    #[Transactional]
    public function __invoke()
    {
        $this->store();
    }

    #[Transactional]
    public function store()
    {
        TransactionalTestUser::create(['name' => 'Mateus']);

        if (self::$shouldFail) {
            throw new \Exception();
        }
    }
}

class TransactionalTestControllerSecondConnection
{
    public static $shouldFail = false;

    #[Transactional(connection: 'second_connection')]
    public function store()
    {
        TransactionalTestUserSecondConnection::create(['name' => 'Mateus']);

        if (self::$shouldFail) {
            throw new \Exception();
        }
    }
}

class TransactionalTestUser extends Model
{
    protected $table = 'users';

    protected $guarded = [];
}

class TransactionalTestUserSecondConnection extends Model
{
    protected $table = 'users';

    protected $guarded = [];

    protected $connection = 'second_connection';
}
