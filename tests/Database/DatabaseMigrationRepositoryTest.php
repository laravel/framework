<?php

namespace Illuminate\Tests\Database;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Support\Collection;
use Illuminate\Database\Migrations\DatabaseMigrationRepository;

class DatabaseMigrationRepositoryTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testGetRanMigrationsListMigrationsByPackage()
    {
        $repo = $this->getRepository();
        $query = m::mock('stdClass');
        $connectionMock = m::mock('Illuminate\Database\Connection');
        $repo->getConnectionResolver()->shouldReceive('connection')->with(null)->andReturn($connectionMock);
        $repo->getConnection()->shouldReceive('table')->once()->with('migrations')->andReturn($query);
        $query->shouldReceive('orderBy')->once()->with('batch', 'asc')->andReturn($query);
        $query->shouldReceive('orderBy')->once()->with('migration', 'asc')->andReturn($query);
        $query->shouldReceive('pluck')->once()->with('migration')->andReturn(new Collection(['bar']));
        $query->shouldReceive('useWritePdo')->once()->andReturn($query);

        $this->assertEquals(['bar'], $repo->getRan());
    }

    public function testGetLastMigrationsGetsAllMigrationsWithTheLatestBatchNumber()
    {
        $repo = $this->getMockBuilder('Illuminate\Database\Migrations\DatabaseMigrationRepository')->setMethods(['getLastBatchNumber'])->setConstructorArgs([
            $resolver = m::mock('Illuminate\Database\ConnectionResolverInterface'), 'migrations',
        ])->getMock();
        $repo->expects($this->once())->method('getLastBatchNumber')->will($this->returnValue(1));
        $query = m::mock('stdClass');
        $connectionMock = m::mock('Illuminate\Database\Connection');
        $repo->getConnectionResolver()->shouldReceive('connection')->with(null)->andReturn($connectionMock);
        $repo->getConnection()->shouldReceive('table')->once()->with('migrations')->andReturn($query);
        $query->shouldReceive('where')->once()->with('batch', 1)->andReturn($query);
        $query->shouldReceive('orderBy')->once()->with('migration', 'desc')->andReturn($query);
        $query->shouldReceive('get')->once()->andReturn(new Collection(['foo']));
        $query->shouldReceive('useWritePdo')->once()->andReturn($query);

        $this->assertEquals(['foo'], $repo->getLast());
    }

    public function testLogMethodInsertsRecordIntoMigrationTable()
    {
        $repo = $this->getRepository();
        $query = m::mock('stdClass');
        $connectionMock = m::mock('Illuminate\Database\Connection');
        $repo->getConnectionResolver()->shouldReceive('connection')->with(null)->andReturn($connectionMock);
        $repo->getConnection()->shouldReceive('table')->once()->with('migrations')->andReturn($query);
        $query->shouldReceive('insert')->once()->with(['migration' => 'bar', 'batch' => 1]);
        $query->shouldReceive('useWritePdo')->once()->andReturn($query);

        $repo->log('bar', 1);
    }

    public function testDeleteMethodRemovesAMigrationFromTheTable()
    {
        $repo = $this->getRepository();
        $query = m::mock('stdClass');
        $connectionMock = m::mock('Illuminate\Database\Connection');
        $repo->getConnectionResolver()->shouldReceive('connection')->with(null)->andReturn($connectionMock);
        $repo->getConnection()->shouldReceive('table')->once()->with('migrations')->andReturn($query);
        $query->shouldReceive('where')->once()->with('migration', 'foo')->andReturn($query);
        $query->shouldReceive('delete')->once();
        $query->shouldReceive('useWritePdo')->once()->andReturn($query);
        $migration = (object) ['migration' => 'foo'];

        $repo->delete($migration);
    }

    public function testGetNextBatchNumberReturnsLastBatchNumberPlusOne()
    {
        $repo = $this->getMockBuilder('Illuminate\Database\Migrations\DatabaseMigrationRepository')->setMethods(['getLastBatchNumber'])->setConstructorArgs([
            m::mock('Illuminate\Database\ConnectionResolverInterface'), 'migrations',
        ])->getMock();
        $repo->expects($this->once())->method('getLastBatchNumber')->will($this->returnValue(1));

        $this->assertEquals(2, $repo->getNextBatchNumber());
    }

    public function testGetLastBatchNumberReturnsMaxBatch()
    {
        $repo = $this->getRepository();
        $query = m::mock('stdClass');
        $connectionMock = m::mock('Illuminate\Database\Connection');
        $repo->getConnectionResolver()->shouldReceive('connection')->with(null)->andReturn($connectionMock);
        $repo->getConnection()->shouldReceive('table')->once()->with('migrations')->andReturn($query);
        $query->shouldReceive('max')->once()->andReturn(1);
        $query->shouldReceive('useWritePdo')->once()->andReturn($query);

        $this->assertEquals(1, $repo->getLastBatchNumber());
    }

    public function testCreateRepositoryCreatesProperDatabaseTable()
    {
        $repo = $this->getRepository();
        $schema = m::mock('stdClass');
        $connectionMock = m::mock('Illuminate\Database\Connection');
        $repo->getConnectionResolver()->shouldReceive('connection')->with(null)->andReturn($connectionMock);
        $repo->getConnection()->shouldReceive('getSchemaBuilder')->once()->andReturn($schema);
        $schema->shouldReceive('create')->once()->with('migrations', m::type('Closure'));

        $repo->createRepository();
    }

    protected function getRepository()
    {
        return new DatabaseMigrationRepository(m::mock('Illuminate\Database\ConnectionResolverInterface'), 'migrations');
    }
}
