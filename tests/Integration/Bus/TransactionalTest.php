<?php

namespace Illuminate\Tests\Integration\Bus;

use Exception;
use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Database\Middleware\Transactional;
use Illuminate\Support\Facades\Bus;
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

        $app[Dispatcher::class]
            ->pipeThrough([Transactional::class])
            ->map([Transact::class => TransactHandler::class]);
    }

    public function testHandlersThatThrowAreRolledBackAutomatically()
    {
        $this->assertDatabaseEmpty('users');

        try {
            Bus::dispatch(new Transact('Taylor Otwell'));
        } catch (Exception) {
            //
        }

        $this->assertDatabaseEmpty('users');
    }
}

class Transact
{
    public function __construct(public $name)
    {
    }
}

class TransactHandler
{
    public function handle(Transact $command)
    {
        DB::table('users')->insert(['name' => $command->name]);

        throw new Exception('Uh-oh!');
    }
}
