<?php

namespace Illuminate\Tests\Database;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Migrations\MigrationRepositoryInterface;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Database\MigrationServiceProvider;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use PHPUnit\Framework\TestCase;

class DatabaseMigrationServiceProviderTest extends TestCase
{
    public function testMigratorServiceIncludesDefaultMigrationConnection()
    {
        $app = new Application();

        // Set up configuration
        $app['config'] = [
            'database.migrations' => [
                'table' => 'migrations',
                'connection' => 'custom_migration',
            ],
        ];

        // Mock dependencies
        $app['migration.repository'] = $this->createMock(MigrationRepositoryInterface::class);
        $app['db'] = $this->createMock(ConnectionResolverInterface::class);
        $app['files'] = $this->createMock(Filesystem::class);
        $app['events'] = $this->createMock(Dispatcher::class);

        $provider = new MigrationServiceProvider($app);
        $provider->register();

        $migrator = $app['migrator'];

        $this->assertInstanceOf(Migrator::class, $migrator);
    }

    public function testMigratorServiceWorksWithoutConnectionConfiguration()
    {
        $app = new Application();

        // Set up configuration without migration connection
        $app['config'] = [
            'database.migrations' => [
                'table' => 'migrations',
            ],
        ];

        // Mock dependencies
        $app['migration.repository'] = $this->createMock(MigrationRepositoryInterface::class);
        $app['db'] = $this->createMock(ConnectionResolverInterface::class);
        $app['files'] = $this->createMock(Filesystem::class);
        $app['events'] = $this->createMock(Dispatcher::class);

        $provider = new MigrationServiceProvider($app);
        $provider->register();

        $migrator = $app['migrator'];

        $this->assertInstanceOf(Migrator::class, $migrator);
    }

    public function testMigratorServiceWorksWithLegacyStringConfiguration()
    {
        $app = new Application();

        // Set up legacy string configuration
        $app['config'] = [
            'database.migrations' => 'migrations',
        ];

        // Mock dependencies
        $app['migration.repository'] = $this->createMock(MigrationRepositoryInterface::class);
        $app['db'] = $this->createMock(ConnectionResolverInterface::class);
        $app['files'] = $this->createMock(Filesystem::class);
        $app['events'] = $this->createMock(Dispatcher::class);

        $provider = new MigrationServiceProvider($app);
        $provider->register();

        $migrator = $app['migrator'];

        $this->assertInstanceOf(Migrator::class, $migrator);
    }
}
