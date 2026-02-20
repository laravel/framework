<?php

namespace Illuminate\Tests\Integration\Database;

use Orchestra\Testbench\TestCase;

class MigrationDefaultConnectionTest extends TestCase
{
    public function testMigratorReceivesDefaultMigrationConnectionFromConfig()
    {
        // Set default migration connection in config
        config()->set('database.migrations.connection', 'custom_migration');

        // Get a fresh migrator instance to ensure it picks up the config
        $this->app->forgetInstance('migrator');
        $migrator = app('migrator');

        // Use reflection to access the protected property
        $reflection = new \ReflectionClass($migrator);
        $property = $reflection->getProperty('defaultMigrationConnection');
        $property->setAccessible(true);

        // Assert that the migrator received the correct default connection
        $this->assertEquals('custom_migration', $property->getValue($migrator));
    }

    public function testMigratorUsesNullWhenNoDefaultMigrationConnectionConfigured()
    {
        // Ensure no default migration connection is set
        config()->set('database.migrations.connection', null);

        // Get a fresh migrator instance
        $this->app->forgetInstance('migrator');
        $migrator = app('migrator');

        // Use reflection to access the protected property
        $reflection = new \ReflectionClass($migrator);
        $property = $reflection->getProperty('defaultMigrationConnection');
        $property->setAccessible(true);

        // Assert that the migrator has no default connection
        $this->assertNull($property->getValue($migrator));
    }

    public function testMigratorHandlesLegacyStringMigrationConfig()
    {
        // Set legacy string configuration for migrations
        config()->set('database.migrations', 'migrations_table_name');

        // Get a fresh migrator instance
        $this->app->forgetInstance('migrator');
        $migrator = app('migrator');

        // Use reflection to access the protected property
        $reflection = new \ReflectionClass($migrator);
        $property = $reflection->getProperty('defaultMigrationConnection');
        $property->setAccessible(true);

        // Assert that no default connection is set when using legacy config
        $this->assertNull($property->getValue($migrator));
    }

    public function testConnectionResolutionPriority()
    {
        // Set default migration connection in config
        config()->set('database.migrations.connection', 'migration_default');

        // Get a fresh migrator instance
        $this->app->forgetInstance('migrator');
        $migrator = app('migrator');

        // Test 1: Specific connection parameter takes highest priority
        $resolver = $this->createMock(\Illuminate\Database\ConnectionResolverInterface::class);
        $connection = $this->createMock(\Illuminate\Database\Connection::class);

        $resolver->expects($this->once())
                 ->method('connection')
                 ->with('specific_connection')
                 ->willReturn($connection);

        // Use reflection to set the resolver
        $reflection = new \ReflectionClass($migrator);
        $resolverProperty = $reflection->getProperty('resolver');
        $resolverProperty->setAccessible(true);
        $resolverProperty->setValue($migrator, $resolver);

        // Test that specific connection is used
        $result = $migrator->resolveConnection('specific_connection');
        $this->assertSame($connection, $result);
    }
}
