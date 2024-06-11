<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class WipeCommandTest extends DatabaseTestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $this->default = $app['config']->get('database.default');

        $app['config']->set([
            'database.connections.second_connection' => [
                'driver' => 'sqlite',
                'database' => ':memory:',
            ],
        ]);
    }

    protected function tearDown(): void
    {
        $this->wipeAndMigrate();

        parent::tearDown();
    }

    protected function wipeAndMigrate(): array
    {
        $this->artisan('db:wipe');
        $this->artisan('db:wipe', ['--database' => 'second_connection']);

        $options = ['--path' => realpath(__DIR__.'/stubs/multiple_databases'), '--realpath' => true];

        $this->artisan('migrate', $options);

        return $options;
    }

    public function testWipingConnectionWithoutWipesConfigArray(): void
    {
        $this->wipeAndMigrate();

        $this->assertTrue(in_array('foo', Schema::connection($this->default)->getTableListing()));
        $this->assertTrue(in_array('bar', Schema::connection('second_connection')->getTableListing()));

        $this->artisan('db:wipe');
        $this->assertFalse(in_array('foo', Schema::connection($this->default)->getTableListing()));
        $this->assertTrue(in_array('bar', Schema::connection('second_connection')->getTableListing()));
    }

    public function testWipingConnectionWithEmptyWipesConfigArray(): void
    {
        $this->wipeAndMigrate();

        $this->assertTrue(in_array('foo', Schema::connection($this->default)->getTableListing()));
        $this->assertTrue(in_array('bar', Schema::connection('second_connection')->getTableListing()));

        $this->app['config']['database.wipes'] = [];

        $this->artisan('db:wipe');
        $this->assertFalse(in_array('foo', Schema::connection($this->default)->getTableListing()));
        $this->assertTrue(in_array('bar', Schema::connection('second_connection')->getTableListing()));
    }

    public function testWipingConnectionWithConnectionInWipesConfigArray(): void
    {
        $this->wipeAndMigrate();

        $this->assertTrue(in_array('foo', Schema::connection($this->default)->getTableListing()));
        $this->assertTrue(in_array('bar', Schema::connection('second_connection')->getTableListing()));

        $this->app['config']['database.wipes'] = ['second_connection'];

        $this->artisan('db:wipe');
        $this->assertTrue(in_array('foo', Schema::connection($this->default)->getTableListing()));
        $this->assertFalse(in_array('bar', Schema::connection('second_connection')->getTableListing()));
    }

    public function testWipingConnectionWithConnectionsInWipesConfigArray(): void
    {
        $this->wipeAndMigrate();

        $this->assertTrue(in_array('foo', Schema::connection($this->default)->getTableListing()));
        $this->assertTrue(in_array('bar', Schema::connection('second_connection')->getTableListing()));

        $this->app['config']['database.wipes'] = [$this->default, 'second_connection'];

        $this->artisan('db:wipe');
        $this->assertFalse(in_array('foo', Schema::connection($this->default)->getTableListing()));
        $this->assertFalse(in_array('bar', Schema::connection('second_connection')->getTableListing()));
    }

    public function testFreshCommandReturningAnExceptionWhenAppHasMultipleDatabaseConnections(): void
    {
        $options = $this->wipeAndMigrate();

        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('General error: 1 table "bar" already exists');

        $this->artisan('migrate:fresh', $options);
    }

    public function testFreshCommandRunningCorrectlyWhenAppHasConfiguredWipesConfigArrayWithMultipleDatabaseConnections(): void
    {
        $options = $this->wipeAndMigrate();

        DB::connection('second_connection')->table('bar')->insert(['bar' => 'bar']);
        $this->assertEquals(1, DB::connection('second_connection')->table('bar')->count());

        $this->app['config']['database.wipes'] = [$this->default, 'second_connection'];

        $this->artisan('migrate:fresh', $options);
        $this->assertEquals(0, DB::connection('second_connection')->table('bar')->count());
    }
}
