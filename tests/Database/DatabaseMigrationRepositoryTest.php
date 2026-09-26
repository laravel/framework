<?php

namespace Illuminate\Tests\Database;

use Closure;
use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionResolver;
use Illuminate\Database\Migrations\DatabaseMigrationRepository;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Schema\Builder as SchemaBuilder;
use Illuminate\Support\Collection;
use Mockery;
use PHPUnit\Framework\TestCase;

class DatabaseMigrationRepositoryTest extends TestCase
{
    public function testGetRanMigrationsListMigrationsByPackage()
    {
        $query = Mockery::mock(QueryBuilder::class);
        $connectionMock = Mockery::mock(Connection::class);
        $repo = $this->getRepository($connectionMock);
        $connectionMock->expects('table')->with('migrations')->andReturn($query);
        $query->expects('orderBy')->with('batch', 'asc')->andReturn($query);
        $query->expects('orderBy')->with('migration', 'asc')->andReturn($query);
        $query->expects('pluck')->with('migration')->andReturn(new Collection(['bar']));
        $query->expects('useWritePdo')->andReturn($query);

        $this->assertEquals(['bar'], $repo->getRan());
    }

    public function testGetLastMigrationsGetsAllMigrationsWithTheLatestBatchNumber()
    {
        $connectionMock = Mockery::mock(Connection::class);
        $repo = $this->getMockBuilder(DatabaseMigrationRepository::class)->onlyMethods(['getLastBatchNumber'])->setConstructorArgs([
            $this->resolver($connectionMock), 'migrations',
        ])->getMock();
        $repo->expects($this->once())->method('getLastBatchNumber')->willReturn(1);
        $query = Mockery::mock(QueryBuilder::class);
        $connectionMock->expects('table')->with('migrations')->andReturn($query);
        $query->expects('where')->with('batch', 1)->andReturn($query);
        $query->expects('orderBy')->with('migration', 'desc')->andReturn($query);
        $query->expects('get')->andReturn(new Collection(['foo']));
        $query->expects('useWritePdo')->andReturn($query);

        $this->assertEquals(['foo'], $repo->getLast());
    }

    public function testLogMethodInsertsRecordIntoMigrationTable()
    {
        $query = Mockery::mock(QueryBuilder::class);
        $connectionMock = Mockery::mock(Connection::class);
        $repo = $this->getRepository($connectionMock);
        $connectionMock->expects('table')->with('migrations')->andReturn($query);
        $query->expects('insert')->with(['migration' => 'bar', 'batch' => 1]);
        $query->expects('useWritePdo')->andReturn($query);

        $repo->log('bar', 1);
    }

    public function testDeleteMethodRemovesAMigrationFromTheTable()
    {
        $query = Mockery::mock(QueryBuilder::class);
        $connectionMock = Mockery::mock(Connection::class);
        $repo = $this->getRepository($connectionMock);
        $connectionMock->expects('table')->with('migrations')->andReturn($query);
        $query->expects('where')->with('migration', 'foo')->andReturn($query);
        $query->expects('delete');
        $query->expects('useWritePdo')->andReturn($query);
        $migration = (object) ['migration' => 'foo'];

        $repo->delete($migration);
    }

    public function testGetNextBatchNumberReturnsLastBatchNumberPlusOne()
    {
        $repo = $this->getMockBuilder(DatabaseMigrationRepository::class)->onlyMethods(['getLastBatchNumber'])->setConstructorArgs([
            $this->resolver(), 'migrations',
        ])->getMock();
        $repo->expects($this->once())->method('getLastBatchNumber')->willReturn(1);

        $this->assertEquals(2, $repo->getNextBatchNumber());
    }

    public function testGetLastBatchNumberReturnsMaxBatch()
    {
        $query = Mockery::mock(QueryBuilder::class);
        $connectionMock = Mockery::mock(Connection::class);
        $repo = $this->getRepository($connectionMock);
        $connectionMock->expects('table')->with('migrations')->andReturn($query);
        $query->expects('max')->andReturn(1);
        $query->expects('useWritePdo')->andReturn($query);

        $this->assertEquals(1, $repo->getLastBatchNumber());
    }

    public function testCreateRepositoryCreatesProperDatabaseTable()
    {
        $schema = Mockery::mock(SchemaBuilder::class);
        $connectionMock = Mockery::mock(Connection::class);
        $repo = $this->getRepository($connectionMock);
        $connectionMock->expects('getSchemaBuilder')->andReturn($schema);
        $schema->expects('create')->with('migrations', Mockery::type(Closure::class));

        $repo->createRepository();
    }

    protected function getRepository($connection = null)
    {
        return new DatabaseMigrationRepository($this->resolver($connection), 'migrations');
    }

    protected function resolver($connection = null)
    {
        $resolver = new ConnectionResolver;
        $resolver->addConnection(null, $connection ?: Mockery::mock(Connection::class));

        return $resolver;
    }
}
