<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Container\Container;
use Illuminate\Contracts\Database\Transactional;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class TransactionalTest extends DatabaseTestCase
{
    protected function afterRefreshingDatabase()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });
    }

    public function testItExecutesAMethodInsideATransaction()
    {
        $controller = new TransactionalTestController();

        Container::getInstance()->call([$controller, 'store']);

        $this->assertDatabaseHas('users', ['name' => 'Mateus']);
    }

    public function testItRollbacksChangesUponFailure()
    {
        $controller = new TransactionalTestController();

        try {
            Container::getInstance()->call([$controller, 'storeFailure']);
        } catch (\Exception) {
            $this->assertDatabaseMissing('users', ['name' => 'Mateus']);

            return;
        }

        $this->fail('Exception was not thrown');
    }
}

class TransactionalTestController
{
    #[Transactional]
    public function store()
    {
        TransactionalTestUser::create(['name' => 'Mateus']);
    }

    #[Transactional]
    public function storeFailure()
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
