<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase;

class WipeCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        $this->artisan('db:wipe', ['--database' => 'first_connection']);
        $this->artisan('db:wipe', ['--database' => 'second_connection']);

        parent::tearDown();
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set([
            'database.default' => 'first_connection',
            'database.connections.first_connection' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
            ],
            'database.connections.second_connection' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
            ],
        ]);
    }

    protected function migrate(): array
    {
        $options = ['--path' => realpath(__DIR__.'/stubs/multiple_databases/migrations'), '--realpath' => true];

        $this->artisan('migrate', $options);

        return $options;
    }

    public function testWipingConnectionWithoutWipesConfigArray(): void
    {
        $this->migrate();

        $this->assertTrue(in_array('foo', Schema::connection('first_connection')->getTableListing()));
        $this->assertTrue(in_array('bar', Schema::connection('second_connection')->getTableListing()));

        $this->artisan('db:wipe');
        $this->assertFalse(in_array('foo', Schema::connection('first_connection')->getTableListing()));
        $this->assertTrue(in_array('bar', Schema::connection('second_connection')->getTableListing()));
    }

    public function testWipingConnectionWithEmptyWipesConfigArray(): void
    {
        $this->migrate();

        $this->assertTrue(in_array('foo', Schema::connection('first_connection')->getTableListing()));
        $this->assertTrue(in_array('bar', Schema::connection('second_connection')->getTableListing()));

        $this->app['config']['database.wipes'] = [];

        $this->artisan('db:wipe');
        $this->assertFalse(in_array('foo', Schema::connection('first_connection')->getTableListing()));
        $this->assertTrue(in_array('bar', Schema::connection('second_connection')->getTableListing()));
    }

    public function testWipingConnectionWithConnectionInWipesConfigArray(): void
    {
        $this->migrate();

        $this->assertTrue(in_array('foo', Schema::connection('first_connection')->getTableListing()));
        $this->assertTrue(in_array('bar', Schema::connection('second_connection')->getTableListing()));

        $this->app['config']['database.wipes'] = ['second_connection'];

        $this->artisan('db:wipe');
        $this->assertTrue(in_array('foo', Schema::connection('first_connection')->getTableListing()));
        $this->assertFalse(in_array('bar', Schema::connection('second_connection')->getTableListing()));
    }

    public function testWipingConnectionWithConnectionsInWipesConfigArray(): void
    {
        $this->migrate();

        $this->assertTrue(in_array('foo', Schema::connection('first_connection')->getTableListing()));
        $this->assertTrue(in_array('bar', Schema::connection('second_connection')->getTableListing()));

        $this->app['config']['database.wipes'] = ['first_connection', 'second_connection'];

        $this->artisan('db:wipe');
        $this->assertFalse(in_array('foo', Schema::connection('first_connection')->getTableListing()));
        $this->assertFalse(in_array('bar', Schema::connection('second_connection')->getTableListing()));
    }

    public function testFreshCommandReturningAnExceptionWhenAppHasMultipleDatabaseConnections(): void
    {
        $options = $this->migrate();

        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('General error: 1 table "bar" already exists');

        $this->artisan('migrate:fresh', $options);
    }

    public function testFreshCommandRunningCorrectlyWhenAppHasConfiguredWipesConfigArrayWithMultipleDatabaseConnections(): void
    {
        $options = $this->migrate();

        DB::connection('second_connection')->table('bar')->insert(['bar' => 'bar']);
        $this->assertEquals(1, DB::connection('second_connection')->table('bar')->count());

        $this->app['config']['database.wipes'] = ['first_connection', 'second_connection'];

        $this->artisan('migrate:fresh', $options);
        $this->assertEquals(0, DB::connection('second_connection')->table('bar')->count());
    }
}
