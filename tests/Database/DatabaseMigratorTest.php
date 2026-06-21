<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Migrations\MigrationRepositoryInterface;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Filesystem\Filesystem;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

class DatabaseMigratorTest extends TestCase
{
    protected function tearDown(): void
    {
        (new ReflectionProperty(Migrator::class, 'connectionResolverCallback'))->setValue(null, null);

        m::close();

        parent::tearDown();
    }

    public function testResolveConnectionUsesDirectVariantWhenConfigured()
    {
        $resolver = m::mock(ConnectionResolverInterface::class);
        $baseConnection = m::mock(Connection::class);
        $directConnection = m::mock(Connection::class);

        $resolver->shouldReceive('connection')->once()->with('pgsql')->andReturn($baseConnection);
        $baseConnection->shouldReceive('hasDirectConnection')->once()->andReturn(true);
        $resolver->shouldReceive('connection')->once()->with('pgsql::direct')->andReturn($directConnection);

        $this->assertSame($directConnection, $this->migrator($resolver)->resolveConnection('pgsql'));
    }

    public function testResolveConnectionLeavesExplicitSuffixesUntouched()
    {
        $resolver = m::mock(ConnectionResolverInterface::class);
        $connection = m::mock(Connection::class);

        $resolver->shouldReceive('connection')->once()->with('pgsql::write')->andReturn($connection);

        $this->assertSame($connection, $this->migrator($resolver)->resolveConnection('pgsql::write'));
    }

    public function testResolveConnectionPassesThroughWhenDirectConnectionIsNotConfigured()
    {
        $resolver = m::mock(ConnectionResolverInterface::class);
        $connection = m::mock(Connection::class);

        $resolver->shouldReceive('connection')->twice()->with('sqlite')->andReturn($connection);
        $connection->shouldReceive('hasDirectConnection')->once()->andReturn(false);

        $this->assertSame($connection, $this->migrator($resolver)->resolveConnection('sqlite'));
    }

    public function testCustomConnectionResolverCallbackKeepsPriority()
    {
        $resolver = m::mock(ConnectionResolverInterface::class);
        $connection = m::mock(Connection::class);

        Migrator::resolveConnectionsUsing(function ($resolver, $name) use ($connection) {
            $this->assertSame('pgsql', $name);

            return $connection;
        });

        $this->assertSame($connection, $this->migrator($resolver)->resolveConnection('pgsql'));
    }

    public function testSetConnectionUsesDirectVariantForRepositoryAndDefaultConnection()
    {
        $resolver = m::mock(ConnectionResolverInterface::class);
        $repository = m::mock(MigrationRepositoryInterface::class);
        $baseConnection = m::mock(Connection::class);

        $resolver->shouldReceive('connection')->once()->with('pgsql')->andReturn($baseConnection);
        $baseConnection->shouldReceive('hasDirectConnection')->once()->andReturn(true);
        $resolver->shouldReceive('setDefaultConnection')->once()->with('pgsql::direct');
        $repository->shouldReceive('setSource')->once()->with('pgsql::direct');

        $migrator = $this->migrator($resolver, $repository);
        $migrator->setConnection('pgsql');

        $this->assertSame('pgsql::direct', $migrator->getConnection());
    }

    public function testSetConnectionNullPreservesDefaultConnectionBehaviorWithoutDirectConnection()
    {
        $resolver = m::mock(ConnectionResolverInterface::class);
        $repository = m::mock(MigrationRepositoryInterface::class);
        $connection = m::mock(Connection::class);

        $resolver->shouldReceive('getDefaultConnection')->once()->andReturn('sqlite');
        $resolver->shouldReceive('connection')->once()->with('sqlite')->andReturn($connection);
        $connection->shouldReceive('hasDirectConnection')->once()->andReturn(false);
        $repository->shouldReceive('setSource')->once()->with(null);
        $resolver->shouldNotReceive('setDefaultConnection');

        $migrator = $this->migrator($resolver, $repository);
        $migrator->setConnection(null);

        $this->assertNull($migrator->getConnection());
    }

    public function testSetConnectionNullUsesDirectVariantWhenDefaultConnectionHasDirectConnection()
    {
        $resolver = m::mock(ConnectionResolverInterface::class);
        $repository = m::mock(MigrationRepositoryInterface::class);
        $connection = m::mock(Connection::class);

        $resolver->shouldReceive('getDefaultConnection')->once()->andReturn('pgsql');
        $resolver->shouldReceive('connection')->once()->with('pgsql')->andReturn($connection);
        $connection->shouldReceive('hasDirectConnection')->once()->andReturn(true);
        $repository->shouldReceive('setSource')->once()->with('pgsql::direct');
        $resolver->shouldReceive('setDefaultConnection')->once()->with('pgsql::direct');

        $migrator = $this->migrator($resolver, $repository);
        $migrator->setConnection(null);

        $this->assertSame('pgsql::direct', $migrator->getConnection());
    }

    public function testRunMethodPreservesDirectConnectionName()
    {
        $resolver = new DatabaseMigratorTestResolver;
        $migrator = $this->migrator($resolver);
        $connection = m::mock(Connection::class);
        $connection->shouldReceive('getNameWithReadWriteType')->once()->andReturn('pgsql::direct');

        $migration = new class($resolver, $this)
        {
            public function __construct(public $resolver, public $test)
            {
                //
            }

            public function up()
            {
                $this->test->assertSame('pgsql::direct', $this->resolver->getDefaultConnection());
            }
        };

        $migrator->runMethodPublic($connection, $migration, 'up');

        $this->assertSame('pgsql', $resolver->getDefaultConnection());
    }

    protected function migrator($resolver, $repository = null)
    {
        return new DatabaseMigratorTestMigrator(
            $repository ?: m::mock(MigrationRepositoryInterface::class),
            $resolver,
            new Filesystem
        );
    }
}

class DatabaseMigratorTestMigrator extends Migrator
{
    public function runMethodPublic($connection, $migration, $method)
    {
        return $this->runMethod($connection, $migration, $method);
    }
}

class DatabaseMigratorTestResolver implements ConnectionResolverInterface
{
    public $default = 'pgsql';

    public function connection($name = null)
    {
        //
    }

    public function getDefaultConnection()
    {
        return $this->default;
    }

    public function setDefaultConnection($name)
    {
        $this->default = $name;
    }
}
