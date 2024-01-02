<?php

namespace Illuminate\Tests\Integration\Routing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Transactional;
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
        Route::get('user', TransactionalTestInvokableController::class);

        $this->get('user')->assertStatus(500);
        $this->assertDatabaseMissing('users', ['name' => 'Mateus']);
    }
}


class TransactionalTestController
{
    public static $shouldFail = false;

    #[Transactional]
    public function store()
    {
        TransactionalTestUser::create(['name' => 'Mateus']);

        if (self::$shouldFail) {
            throw new \Exception();
        }
    }
}

class TransactionalTestInvokableController
{
    #[Transactional]
    public function __invoke()
    {
        TransactionalTestUser::create(['name' => 'Mateus']);

        throw new \Exception();
    }
}

class TransactionalTestUser extends Model
{
    protected $table = 'users';

    protected $guarded = [];
}

