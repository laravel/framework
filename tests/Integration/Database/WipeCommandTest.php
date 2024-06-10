<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Orchestra\Testbench\TestCase;

class WipeCommandTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        $app['config']->set([
            'database.default' => 'first_connection',
            'database.connections.first_connection' => [
                'driver' => 'sqlite',
                'database' => realpath(__DIR__.'/stubs/multiple_databases/databases/first.sqlite')
            ],
            'database.connections.second_connection' => [
                'driver' => 'sqlite',
                'database' => realpath(__DIR__.'/stubs/multiple_databases/databases/second.sqlite')
            ],
        ]);
    }

    protected function tearDown(): void
    {
        $this->artisan('db:wipe', ['--database' => 'first_connection']);
        $this->artisan('db:wipe', ['--database' => 'second_connection']);

        parent::tearDown();
    }

    protected function migrate(): array
    {
        $options = ['--path' => realpath(__DIR__.'/stubs/multiple_databases/migrations'), '--realpath' => true];

        $this->artisan('migrate', $options);

        return $options;
    }

    public function testFreshCommandReturningAnExceptionWhenAppHasMultipleDatabaseConnections(): void
    {
        $options = $this->migrate();

        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('General error: 1 table "bar" already exists');

        $this->artisan('migrate:fresh', $options);
    }

    public function testFreshCommandRunningCorrectlyWhenAppHasMultipleDatabaseConnections(): void
    {
        $options = $this->migrate();

        DB::connection('second_connection')->table('bar')->insert(['bar' => 'bar']);
        $this->assertEquals(1, DB::connection('second_connection')->table('bar')->count());

        $this->app['config']['database.wipes'] = ['first_connection','second_connection'];

        $this->artisan('migrate:fresh', $options);
        $this->assertEquals(0, DB::connection('second_connection')->table('bar')->count());
    }
}
