<?php

namespace Illuminate\Tests\Database;

use Illuminate\Console\OutputStyle;
use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Migrations\DatabaseMigrationRepository;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Str;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class DatabaseMigratorIntegrationTest extends TestCase
{
    protected $db;
    protected $migrator;

    /**
     * Bootstrap Eloquent.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->db = $db = new DB;

        $db->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ]);

        $db->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ], 'sqlite2');

        $db->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
        ], 'sqlite3');

        $db->setAsGlobal();

        $container = new Container;
        $container->instance('db', $db->getDatabaseManager());
        $container->bind('db.schema', function ($app) {
            return $app['db']->connection()->getSchemaBuilder();
        });

        Facade::setFacadeApplication($container);

        $this->migrator = new Migrator(
            $repository = new DatabaseMigrationRepository($db->getDatabaseManager(), 'migrations'),
            $db->getDatabaseManager(),
            new Filesystem
        );

        $output = m::mock(OutputStyle::class);
        $output->shouldReceive('write');
        $output->shouldReceive('writeln');
        $output->shouldReceive('newLineWritten');

        $this->migrator->setOutput($output);

        if (! $repository->repositoryExists()) {
            $repository->createRepository();
        }

        $repository2 = new DatabaseMigrationRepository($db->getDatabaseManager(), 'migrations');
        $repository2->setSource('sqlite2');

        if (! $repository2->repositoryExists()) {
            $repository2->createRepository();
        }
    }

    protected function tearDown(): void
    {
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);
    }

    public function testBasicMigrationOfSingleFolder()
    {
        $ran = $this->migrator->run([__DIR__.'/migrations/one']);

        $this->assertTrue($this->db->schema()->hasTable('users'));
        $this->assertTrue($this->db->schema()->hasTable('password_resets'));

        $this->assertTrue(str_contains($ran[0], 'users'));
        $this->assertTrue(str_contains($ran[1], 'password_resets'));
    }

    public function testMigrationsDefaultConnectionCanBeChanged()
    {
        $ran = $this->migrator->usingConnection('sqlite2', function () {
            return $this->migrator->run([__DIR__.'/migrations/one'], ['database' => 'sqllite3']);
        });

        $this->assertFalse($this->db->schema()->hasTable('users'));
        $this->assertFalse($this->db->schema()->hasTable('password_resets'));
        $this->assertTrue($this->db->schema('sqlite2')->hasTable('users'));
        $this->assertTrue($this->db->schema('sqlite2')->hasTable('password_resets'));
        $this->assertFalse($this->db->schema('sqlite3')->hasTable('users'));
        $this->assertFalse($this->db->schema('sqlite3')->hasTable('password_resets'));

        $this->assertTrue(Str::contains($ran[0], 'users'));
        $this->assertTrue(Str::contains($ran[1], 'password_resets'));
    }

    public function testMigrationsCanEachDefineConnection()
    {
        $ran = $this->migrator->run([__DIR__.'/migrations/connection_configured']);

        $this->assertFalse($this->db->schema()->hasTable('failed_jobs'));
        $this->assertFalse($this->db->schema()->hasTable('jobs'));
        $this->assertFalse($this->db->schema('sqlite2')->hasTable('failed_jobs'));
        $this->assertFalse($this->db->schema('sqlite2')->hasTable('jobs'));
        $this->assertTrue($this->db->schema('sqlite3')->hasTable('failed_jobs'));
        $this->assertTrue($this->db->schema('sqlite3')->hasTable('jobs'));

        $this->assertTrue(Str::contains($ran[0], 'failed_jobs'));
        $this->assertTrue(Str::contains($ran[1], 'jobs'));
    }

    public function testMigratorCannotChangeDefinedMigrationConnection()
    {
        $ran = $this->migrator->usingConnection('sqlite2', function () {
            return $this->migrator->run([__DIR__.'/migrations/connection_configured']);
        });

        $this->assertFalse($this->db->schema()->hasTable('failed_jobs'));
        $this->assertFalse($this->db->schema()->hasTable('jobs'));
        $this->assertFalse($this->db->schema('sqlite2')->hasTable('failed_jobs'));
        $this->assertFalse($this->db->schema('sqlite2')->hasTable('jobs'));
        $this->assertTrue($this->db->schema('sqlite3')->hasTable('failed_jobs'));
        $this->assertTrue($this->db->schema('sqlite3')->hasTable('jobs'));

        $this->assertTrue(Str::contains($ran[0], 'failed_jobs'));
        $this->assertTrue(Str::contains($ran[1], 'jobs'));
    }

    public function testMigrationsCanBeRolledBack()
    {
        $this->migrator->run([__DIR__.'/migrations/one']);
        $this->assertTrue($this->db->schema()->hasTable('users'));
        $this->assertTrue($this->db->schema()->hasTable('password_resets'));
        $rolledBack = $this->migrator->rollback([__DIR__.'/migrations/one']);
        $this->assertFalse($this->db->schema()->hasTable('users'));
        $this->assertFalse($this->db->schema()->hasTable('password_resets'));

        $this->assertTrue(str_contains($rolledBack[0], 'password_resets'));
        $this->assertTrue(str_contains($rolledBack[1], 'users'));
    }

    public function testMigrationsCanBeReset()
    {
        $this->migrator->run([__DIR__.'/migrations/one']);
        $this->assertTrue($this->db->schema()->hasTable('users'));
        $this->assertTrue($this->db->schema()->hasTable('password_resets'));
        $rolledBack = $this->migrator->reset([__DIR__.'/migrations/one']);
        $this->assertFalse($this->db->schema()->hasTable('users'));
        $this->assertFalse($this->db->schema()->hasTable('password_resets'));

        $this->assertTrue(str_contains($rolledBack[0], 'password_resets'));
        $this->assertTrue(str_contains($rolledBack[1], 'users'));
    }

    public function testNoErrorIsThrownWhenNoOutstandingMigrationsExist()
    {
        $this->migrator->run([__DIR__.'/migrations/one']);
        $this->assertTrue($this->db->schema()->hasTable('users'));
        $this->assertTrue($this->db->schema()->hasTable('password_resets'));
        $this->migrator->run([__DIR__.'/migrations/one']);
    }

    public function testNoErrorIsThrownWhenNothingToRollback()
    {
        $this->migrator->run([__DIR__.'/migrations/one']);
        $this->assertTrue($this->db->schema()->hasTable('users'));
        $this->assertTrue($this->db->schema()->hasTable('password_resets'));
        $this->migrator->rollback([__DIR__.'/migrations/one']);
        $this->assertFalse($this->db->schema()->hasTable('users'));
        $this->assertFalse($this->db->schema()->hasTable('password_resets'));
        $this->migrator->rollback([__DIR__.'/migrations/one']);
    }

    public function testMigrationsCanRunAcrossMultiplePaths()
    {
        $this->migrator->run([__DIR__.'/migrations/one', __DIR__.'/migrations/two']);
        $this->assertTrue($this->db->schema()->hasTable('users'));
        $this->assertTrue($this->db->schema()->hasTable('password_resets'));
        $this->assertTrue($this->db->schema()->hasTable('flights'));
    }

    public function testMigrationsCanBeRolledBackAcrossMultiplePaths()
    {
        $this->migrator->run([__DIR__.'/migrations/one', __DIR__.'/migrations/two']);
        $this->assertTrue($this->db->schema()->hasTable('users'));
        $this->assertTrue($this->db->schema()->hasTable('password_resets'));
        $this->assertTrue($this->db->schema()->hasTable('flights'));
        $this->migrator->rollback([__DIR__.'/migrations/one', __DIR__.'/migrations/two']);
        $this->assertFalse($this->db->schema()->hasTable('users'));
        $this->assertFalse($this->db->schema()->hasTable('password_resets'));
        $this->assertFalse($this->db->schema()->hasTable('flights'));
    }

    public function testMigrationsCanBeResetAcrossMultiplePaths()
    {
        $this->migrator->run([__DIR__.'/migrations/one', __DIR__.'/migrations/two']);
        $this->assertTrue($this->db->schema()->hasTable('users'));
        $this->assertTrue($this->db->schema()->hasTable('password_resets'));
        $this->assertTrue($this->db->schema()->hasTable('flights'));
        $this->migrator->reset([__DIR__.'/migrations/one', __DIR__.'/migrations/two']);
        $this->assertFalse($this->db->schema()->hasTable('users'));
        $this->assertFalse($this->db->schema()->hasTable('password_resets'));
        $this->assertFalse($this->db->schema()->hasTable('flights'));
    }

    public function testMigrationsCanBeProperlySortedAcrossMultiplePaths()
    {
        $paths = [__DIR__.'/migrations/multi_path/vendor', __DIR__.'/migrations/multi_path/app'];

        $migrationsFilesFullPaths = array_values($this->migrator->getMigrationFiles($paths));

        $expected = [
            __DIR__.'/migrations/multi_path/app/2016_01_01_000000_create_users_table.php', // This file was not created on the "vendor" directory on purpose
            __DIR__.'/migrations/multi_path/vendor/2016_01_01_200000_create_flights_table.php', // This file was not created on the "app" directory on purpose
            __DIR__.'/migrations/multi_path/app/2019_08_08_000001_rename_table_one.php',
            __DIR__.'/migrations/multi_path/app/2019_08_08_000002_rename_table_two.php',
            __DIR__.'/migrations/multi_path/app/2019_08_08_000003_rename_table_three.php',
            __DIR__.'/migrations/multi_path/app/2019_08_08_000004_rename_table_four.php',
            __DIR__.'/migrations/multi_path/app/2019_08_08_000005_create_table_one.php',
            __DIR__.'/migrations/multi_path/app/2019_08_08_000006_create_table_two.php',
            __DIR__.'/migrations/multi_path/vendor/2019_08_08_000007_create_table_three.php', // This file was not created on the "app" directory on purpose
            __DIR__.'/migrations/multi_path/app/2019_08_08_000008_create_table_four.php',
        ];

        $this->assertEquals($expected, $migrationsFilesFullPaths);
    }

    public function testConnectionPriorToMigrationIsNotChangedAfterMigration()
    {
        $this->migrator->setConnection('default');
        $this->migrator->run([__DIR__.'/migrations/one'], ['database' => 'sqlite2']);
        $this->assertSame('default', $this->migrator->getConnection());
    }

    public function testConnectionPriorToMigrationIsNotChangedAfterRollback()
    {
        $this->migrator->setConnection('default');
        $this->migrator->run([__DIR__.'/migrations/one'], ['database' => 'sqlite2']);
        $this->migrator->rollback([__DIR__.'/migrations/one'], ['database' => 'sqlite2']);
        $this->assertSame('default', $this->migrator->getConnection());
    }

    public function testConnectionPriorToMigrationIsNotChangedWhenNoOutstandingMigrationsExist()
    {
        $this->migrator->setConnection('default');
        $this->migrator->run([__DIR__.'/migrations/one'], ['database' => 'sqlite2']);
        $this->migrator->setConnection('default');
        $this->migrator->run([__DIR__.'/migrations/one'], ['database' => 'sqlite2']);
        $this->assertSame('default', $this->migrator->getConnection());
    }

    public function testConnectionPriorToMigrationIsNotChangedWhenNothingToRollback()
    {
        $this->migrator->setConnection('default');
        $this->migrator->run([__DIR__.'/migrations/one'], ['database' => 'sqlite2']);
        $this->migrator->rollback([__DIR__.'/migrations/one'], ['database' => 'sqlite2']);
        $this->migrator->rollback([__DIR__.'/migrations/one'], ['database' => 'sqlite2']);
        $this->assertSame('default', $this->migrator->getConnection());
    }

    public function testConnectionPriorToMigrationIsNotChangedAfterMigrateReset()
    {
        $this->migrator->setConnection('default');
        $this->migrator->run([__DIR__.'/migrations/one'], ['database' => 'sqlite2']);
        $this->migrator->reset([__DIR__.'/migrations/one'], ['database' => 'sqlite2']);
        $this->assertSame('default', $this->migrator->getConnection());
    }
}
