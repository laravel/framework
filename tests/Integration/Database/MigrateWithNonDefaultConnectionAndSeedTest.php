<?php

namespace Illuminate\Tests\Integration\Database;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\Attributes\RequiresDatabase;
use Orchestra\Testbench\TestCase;

#[RequiresDatabase('mysql')]
class MigrateWithNonDefaultConnectionAndSeedTest extends TestCase
{
    protected function defineEnvironment($app)
    {
        $app['config']->set('database.default', 'mysql');

        $app['config']->set('database.connections.mysql', [
            'driver' => 'mysql',
            'host' => '127.0.0.1',
            'port' => '3306',
            'database' => 'forge',
            'username' => 'root',
            'password' => '',
        ]);

        $app['config']->set('database.connections.testing', [
            'driver' => 'mysql',
            'host' => '127.0.0.1',
            'port' => '3306',
            'database' => 'testing_db_name',
            'username' => 'root',
            'password' => '',
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->dropAllTables('mysql');
        $this->dropAllTables('testing');
    }

    protected function tearDown(): void
    {
        $this->dropAllTables('mysql');
        $this->dropAllTables('testing');

        foreach (array_keys($this->app['db']->getConnections()) as $name) {
            $this->app['db']->purge($name);
        }

        parent::tearDown();
    }

    protected function dropAllTables($connection)
    {
        $pdo = DB::connection($connection)->getPdo();

        foreach (['people', 'migrations'] as $table) {
            $pdo->exec("DROP TABLE IF EXISTS `{$table}`");
        }
    }

    public function testMigratingOnANonDefaultConnectionCreatesTheMigrationsTableThere()
    {
        $this->artisan('migrate', [
            '--database' => 'testing',
            '--path' => realpath(__DIR__.'/stubs/migrate-connection'),
            '--realpath' => true,
            '--seed' => true,
            '--seeder' => MigrateWithNonDefaultConnectionAndSeedTestSeeder::class,
        ])->run();

        $this->assertTrue(Schema::connection('testing')->hasTable('migrations'), 'migrations table missing on the [testing] connection.');
        $this->assertDatabaseHas('migrations', [
            'migration' => '2014_10_12_000000_create_people_table',
        ], 'testing');

        $this->assertTrue(Schema::connection('testing')->hasTable('people'), 'people table missing on the [testing] connection.');
        $this->assertDatabaseHas('people', ['name' => 'seeded'], 'testing');

        $this->assertFalse(Schema::connection('mysql')->hasTable('migrations'), 'migrations table was created on the default [mysql] connection instead of [testing].');
    }
}

class MigrateWithNonDefaultConnectionAndSeedTestSeeder extends Seeder
{
    public function run()
    {
        DB::table('people')->insert(['name' => 'seeded']);
    }
}
