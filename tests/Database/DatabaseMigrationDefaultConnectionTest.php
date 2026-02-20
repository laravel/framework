<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Migrations\DatabaseMigrationRepository;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Filesystem\Filesystem;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class DatabaseMigrationDefaultConnectionTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testMigratorUsesDefaultMigrationConnection()
    {
        $resolver = m::mock(ConnectionResolverInterface::class);
        $repository = m::mock(DatabaseMigrationRepository::class);
        $files = m::mock(Filesystem::class);

        // Test case: No specific connection provided, should use default migration connection
        $migrator = new Migrator($repository, $resolver, $files, null, 'custom_migration');

        $connection = m::mock(Connection::class);
        $resolver->shouldReceive('connection')->once()->with('custom_migration')->andReturn($connection);

        $result = $migrator->resolveConnection(null);

        $this->assertSame($connection, $result);
    }

    public function testMigratorUsesSpecificConnectionOverDefault()
    {
        $resolver = m::mock(ConnectionResolverInterface::class);
        $repository = m::mock(DatabaseMigrationRepository::class);
        $files = m::mock(Filesystem::class);

        // Test case: Specific connection provided, should use it instead of default migration connection
        $migrator = new Migrator($repository, $resolver, $files, null, 'custom_migration');

        $connection = m::mock(Connection::class);
        $resolver->shouldReceive('connection')->once()->with('specific_connection')->andReturn($connection);

        $result = $migrator->resolveConnection('specific_connection');

        $this->assertSame($connection, $result);
    }

    public function testMigratorUsesCurrentConnectionOverDefaultMigration()
    {
        $resolver = m::mock(ConnectionResolverInterface::class);
        $repository = m::mock(DatabaseMigrationRepository::class);
        $repository->shouldReceive('setSource')->once()->with('current_connection');
        $files = m::mock(Filesystem::class);

        // Test case: Current connection is set, should use it over default migration connection
        $migrator = new Migrator($repository, $resolver, $files, null, 'custom_migration');

        $resolver->shouldReceive('setDefaultConnection')->once()->with('current_connection');
        $migrator->setConnection('current_connection');

        $connection = m::mock(Connection::class);
        $resolver->shouldReceive('connection')->once()->with('current_connection')->andReturn($connection);

        $result = $migrator->resolveConnection(null);

        $this->assertSame($connection, $result);
    }

    public function testMigratorFallsBackToDefaultWhenNoDefaultMigrationConnectionConfigured()
    {
        $resolver = m::mock(ConnectionResolverInterface::class);
        $repository = m::mock(DatabaseMigrationRepository::class);
        $files = m::mock(Filesystem::class);

        // Test case: No default migration connection configured, should fall back to null (default)
        $migrator = new Migrator($repository, $resolver, $files, null, null);

        $connection = m::mock(Connection::class);
        $resolver->shouldReceive('connection')->once()->with(null)->andReturn($connection);

        $result = $migrator->resolveConnection(null);

        $this->assertSame($connection, $result);
    }

    public function testConnectionPriorityOrder()
    {
        $resolver = m::mock(ConnectionResolverInterface::class);
        $repository = m::mock(DatabaseMigrationRepository::class);
        $files = m::mock(Filesystem::class);

        $migrator = new Migrator($repository, $resolver, $files, null, 'default_migration');

        // Mock expectations for first setConnection call
        $repository->shouldReceive('setSource')->once()->with('current_connection');
        $resolver->shouldReceive('setDefaultConnection')->once()->with('current_connection');
        $migrator->setConnection('current_connection');

        $connection = m::mock(Connection::class);

        // Test priority: specific connection > current connection > default migration connection
        $resolver->shouldReceive('connection')->once()->with('specific_connection')->andReturn($connection);
        $result = $migrator->resolveConnection('specific_connection');
        $this->assertSame($connection, $result);

        // Test priority: current connection > default migration connection
        $resolver->shouldReceive('connection')->once()->with('current_connection')->andReturn($connection);
        $result = $migrator->resolveConnection(null);
        $this->assertSame($connection, $result);

        // Test priority: default migration connection when no current connection
        $repository->shouldReceive('setSource')->once()->with(null);
        $migrator->setConnection(null);
        $resolver->shouldReceive('connection')->once()->with('default_migration')->andReturn($connection);
        $result = $migrator->resolveConnection(null);
        $this->assertSame($connection, $result);
    }
}
