<?php

namespace Illuminate\Tests\Integration\Http;

use Exception;
use Illuminate\Database\Middleware\Transactional;
use Illuminate\Support\Facades\DB;
use Orchestra\Testbench\TestCase;

class TransactionalTest extends TestCase
{
    protected function defineEnvironment($app)
    {
        $app['db.schema']->create('users', static function ($table) {
            $table->id();
            $table->string('name');
        });

        $app['router']->post('taylor', TaylorOtwellController::class)->middleware(Transactional::class);
    }

    public function testRoutesThatThrowAreRolledBackAutomatically()
    {
        $this->assertDatabaseEmpty('users');

        try {
            $this->withoutExceptionHandling()->post('taylor');
        } catch (Exception) {
            //
        }

        $this->assertDatabaseEmpty('users');
    }
}

class TaylorOtwellController
{
    public function __invoke()
    {
        DB::table('users')->insert(['name' => 'Taylor Otwell']);

        throw new Exception('Uh-oh!');
    }
}
