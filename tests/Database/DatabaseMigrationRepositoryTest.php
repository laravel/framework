<?php

namespace Illuminate\Tests\Database;

use Closure;
use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Migrations\DatabaseMigrationRepository;
use Illuminate\Support\Collection;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use stdClass;

class DatabaseMigrationRepositoryTest extends TestCase
{
    public function testGetRanMigrationsListMigrationsByPackage()
    {
        $repo = $this->getRepository();
        $query = m::mock(stdClass::class);
        $connectionMock = m::mock(Connection::class);
        $repo->getConnectionResolver()->expects('connection')->times(2)->with(null)->andReturn($connectionMock);
        $repo->getConnection()->expects('table')->with('migrations')->andReturn($query);
        $query->expects('orderBy')->with('batch', 'asc')->andReturn($query);
        $query->expects('orderBy')->with('migration', 'asc')->andReturn($query);
        $query->expects('pluck')->with('migration')->andReturn(new Collection(['bar']));
        $query->expects('useWritePdo')->andReturn($query);

        $this->assertEquals(['bar'], $repo->getRan());
    }

    public function testGetLastMigrationsGetsAllMigrationsWithTheLatestBatchNumber()
    {
        $resolver = m::mock(ConnectionResolverInterface::class);
        $repo = $this->getMockBuilder(DatabaseMigrationRepository::class)->onlyMethods(['getLastBatchNumber'])->setConstructorArgs([
            $resolver, 'migrations',
        ])->getMock();
        $repo->expects($this->once())->method('getLastBatchNumber')->willReturn(1);
        $query = m::mock(stdClass::class);
        $connectionMock = m::mock(Connection::class);
        $repo->getConnectionResolver()->expects('connection')->times(2)->with(null)->andReturn($connectionMock);
        $repo->getConnection()->expects('table')->with('migrations')->andReturn($query);
        $query->expects('where')->with('batch', 1)->andReturn($query);
        $query->expects('orderBy')->with('migration', 'desc')->andReturn($query);
        $query->expects('get')->andReturn(new Collection(['foo']));
        $query->expects('useWritePdo')->andReturn($query);

        $this->assertEquals(['foo'], $repo->getLast());
    }

    public function testLogMethodInsertsRecordIntoMigrationTable()
    {
        $repo = $this->getRepository();
        $query = m::mock(stdClass::class);
        $connectionMock = m::mock(Connection::class);
        $repo->getConnectionResolver()->expects('connection')->times(2)->with(null)->andReturn($connectionMock);
        $repo->getConnection()->expects('table')->with('migrations')->andReturn($query);
        $query->expects('insert')->with(['migration' => 'bar', 'batch' => 1]);
        $query->expects('useWritePdo')->andReturn($query);

        $repo->log('bar', 1);
    }

    public function testDeleteMethodRemovesAMigrationFromTheTable()
    {
        $repo = $this->getRepository();
        $query = m::mock(stdClass::class);
        $connectionMock = m::mock(Connection::class);
        $repo->getConnectionResolver()->expects('connection')->times(2)->with(null)->andReturn($connectionMock);
        $repo->getConnection()->expects('table')->with('migrations')->andReturn($query);
        $query->expects('where')->with('migration', 'foo')->andReturn($query);
        $query->expects('delete');
        $query->expects('useWritePdo')->andReturn($query);
        $migration = (object) ['migration' => 'foo'];

        $repo->delete($migration);
    }

    public function testGetNextBatchNumberReturnsLastBatchNumberPlusOne()
    {
        $repo = $this->getMockBuilder(DatabaseMigrationRepository::class)->onlyMethods(['getLastBatchNumber'])->setConstructorArgs([
            m::mock(ConnectionResolverInterface::class), 'migrations',
        ])->getMock();
        $repo->expects($this->once())->method('getLastBatchNumber')->willReturn(1);

        $this->assertEquals(2, $repo->getNextBatchNumber());
    }

    public function testGetLastBatchNumberReturnsMaxBatch()
    {
        $repo = $this->getRepository();
        $query = m::mock(stdClass::class);
        $connectionMock = m::mock(Connection::class);
        $repo->getConnectionResolver()->expects('connection')->times(2)->with(null)->andReturn($connectionMock);
        $repo->getConnection()->expects('table')->with('migrations')->andReturn($query);
        $query->expects('max')->andReturn(1);
        $query->expects('useWritePdo')->andReturn($query);

        $this->assertEquals(1, $repo->getLastBatchNumber());
    }

    public function testCreateRepositoryCreatesProperDatabaseTable()
    {
        $repo = $this->getRepository();
        $schema = m::mock(stdClass::class);
        $connectionMock = m::mock(Connection::class);
        $repo->getConnectionResolver()->expects('connection')->times(2)->with(null)->andReturn($connectionMock);
        $repo->getConnection()->expects('getSchemaBuilder')->andReturn($schema);
        $schema->expects('create')->with('migrations', m::type(Closure::class));

        $repo->createRepository();
    }

    protected function getRepository()
    {
        return new DatabaseMigrationRepository(m::mock(ConnectionResolverInterface::class), 'migrations');
    }
}
