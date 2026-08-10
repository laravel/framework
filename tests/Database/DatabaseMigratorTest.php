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
    }

    public function testResolveConnectionUsesDirectVariantWhenConfigured()
    {
        $resolver = m::mock(ConnectionResolverInterface::class);
        $baseConnection = m::mock(Connection::class);
        $directConnection = m::mock(Connection::class);

        $resolver->expects('connection')->with('pgsql')->andReturn($baseConnection);
        $baseConnection->expects('hasDirectConnection')->andReturn(true);
        $resolver->expects('connection')->with('pgsql::direct')->andReturn($directConnection);

        $this->assertSame($directConnection, $this->migrator($resolver)->resolveConnection('pgsql'));
    }

    public function testResolveConnectionLeavesExplicitSuffixesUntouched()
    {
        $resolver = m::mock(ConnectionResolverInterface::class);
        $connection = m::mock(Connection::class);

        $resolver->expects('connection')->with('pgsql::write')->andReturn($connection);

        $this->assertSame($connection, $this->migrator($resolver)->resolveConnection('pgsql::write'));
    }

    public function testResolveConnectionPassesThroughWhenDirectConnectionIsNotConfigured()
    {
        $resolver = m::mock(ConnectionResolverInterface::class);
        $connection = m::mock(Connection::class);

        $resolver->expects('connection')->times(2)->with('sqlite')->andReturn($connection);
        $connection->expects('hasDirectConnection')->andReturn(false);

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

        $resolver->expects('connection')->with('pgsql')->andReturn($baseConnection);
        $baseConnection->expects('hasDirectConnection')->andReturn(true);
        $resolver->expects('setDefaultConnection')->with('pgsql::direct');
        $repository->expects('setSource')->with('pgsql::direct');

        $migrator = $this->migrator($resolver, $repository);
        $migrator->setConnection('pgsql');

        $this->assertSame('pgsql::direct', $migrator->getConnection());
    }

    public function testSetConnectionNullPreservesDefaultConnectionBehaviorWithoutDirectConnection()
    {
        $resolver = m::mock(ConnectionResolverInterface::class);
        $repository = m::mock(MigrationRepositoryInterface::class);
        $connection = m::mock(Connection::class);

        $resolver->expects('getDefaultConnection')->andReturn('sqlite');
        $resolver->expects('connection')->with('sqlite')->andReturn($connection);
        $connection->expects('hasDirectConnection')->andReturn(false);
        $repository->expects('setSource')->with(null);
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

        $resolver->expects('getDefaultConnection')->andReturn('pgsql');
        $resolver->expects('connection')->with('pgsql')->andReturn($connection);
        $connection->expects('hasDirectConnection')->andReturn(true);
        $repository->expects('setSource')->with('pgsql::direct');
        $resolver->expects('setDefaultConnection')->with('pgsql::direct');

        $migrator = $this->migrator($resolver, $repository);
        $migrator->setConnection(null);

        $this->assertSame('pgsql::direct', $migrator->getConnection());
    }

    public function testRunMethodPreservesDirectConnectionName()
    {
        $resolver = new DatabaseMigratorTestResolver;
        $migrator = $this->migrator($resolver);
        $connection = m::mock(Connection::class);
        $connection->expects('getNameWithReadWriteType')->andReturn('pgsql::direct');

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

    public function testUsingConnectionRestoresOriginalDefaultConnectionWithoutReroutingToDirect()
    {
        $resolver = new DatabaseMigratorTestResolver;
        $resolver->connections['pgsql'] = new DatabaseMigratorTestConnection(true);

        $repository = new DatabaseMigratorTestRepository;
        $migrator = $this->migrator($resolver, $repository);

        $migrator->usingConnection(null, function () use ($migrator, $resolver, $repository) {
            $this->assertSame('pgsql::direct', $migrator->getConnection());
            $this->assertSame('pgsql::direct', $resolver->getDefaultConnection());
            $this->assertSame('pgsql::direct', $repository->source);
        });

        $this->assertNull($migrator->getConnection());
        $this->assertSame('pgsql', $resolver->getDefaultConnection());
        $this->assertNull($repository->source);
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

    public $connections = [];

    public function connection($name = null)
    {
        return $this->connections[$name ?? $this->default];
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

class DatabaseMigratorTestConnection extends Connection
{
    public function __construct(protected $hasDirectConnection)
    {
        //
    }

    public function hasDirectConnection()
    {
        return $this->hasDirectConnection;
    }
}

class DatabaseMigratorTestRepository implements MigrationRepositoryInterface
{
    public $source;

    public function getRan()
    {
        //
    }

    public function getMigrations($steps)
    {
        //
    }

    public function getMigrationsByBatch($batch)
    {
        //
    }

    public function getLast()
    {
        //
    }

    public function getMigrationBatches()
    {
        //
    }

    public function log($file, $batch)
    {
        //
    }

    public function delete($migration)
    {
        //
    }

    public function getNextBatchNumber()
    {
        //
    }

    public function createRepository()
    {
        //
    }

    public function deleteRepository()
    {
        //
    }

    public function repositoryExists()
    {
        //
    }

    public function setSource($name)
    {
        $this->source = $name;
    }
}
