<?php

namespace Illuminate\Tests\Database;

use Closure;
use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionResolver;
use Illuminate\Database\Migrations\DatabaseMigrationRepository;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Schema\Builder as SchemaBuilder;
use Illuminate\Support\Collection;
use JMac\Testing\Double;
use JMac\Testing\Integrations\PHPUnit\VerifiesDoubles;
use JMac\Testing\Matching\Argument;
use PHPUnit\Framework\TestCase;

class DatabaseMigrationRepositoryTest extends TestCase
{
    use VerifiesDoubles;

    public function testGetRanMigrationsListMigrationsByPackage()
    {
        $query = Double::for(QueryBuilder::class);
        $connectionMock = Double::for(Connection::class);
        $repo = $this->getRepository($connectionMock);
        $connectionMock->expects('table')->with('migrations')->returns($query);
        $query->expects('orderBy')->with('batch', 'asc')->returns($query);
        $query->expects('orderBy')->with('migration', 'asc')->returns($query);
        $query->expects('pluck')->with('migration')->returns(new Collection(['bar']));
        $query->expects('useWritePdo')->returns($query);

        $this->assertEquals(['bar'], $repo->getRan());
    }

    public function testGetLastMigrationsGetsAllMigrationsWithTheLatestBatchNumber()
    {
        $connectionMock = Double::for(Connection::class);
        $repo = $this->getMockBuilder(DatabaseMigrationRepository::class)->onlyMethods(['getLastBatchNumber'])->setConstructorArgs([
            $this->resolver($connectionMock), 'migrations',
        ])->getMock();
        $repo->expects($this->once())->method('getLastBatchNumber')->willReturn(1);
        $query = Double::for(QueryBuilder::class);
        $connectionMock->expects('table')->with('migrations')->returns($query);
        $query->expects('where')->with('batch', 1)->returns($query);
        $query->expects('orderBy')->with('migration', 'desc')->returns($query);
        $query->expects('get')->returns(new Collection(['foo']));
        $query->expects('useWritePdo')->returns($query);

        $this->assertEquals(['foo'], $repo->getLast());
    }

    public function testLogMethodInsertsRecordIntoMigrationTable()
    {
        $query = Double::for(QueryBuilder::class);
        $connectionMock = Double::for(Connection::class);
        $repo = $this->getRepository($connectionMock);
        $connectionMock->expects('table')->with('migrations')->returns($query);
        $query->expects('insert')->with(['migration' => 'bar', 'batch' => 1]);
        $query->expects('useWritePdo')->returns($query);

        $repo->log('bar', 1);
    }

    public function testDeleteMethodRemovesAMigrationFromTheTable()
    {
        $query = Double::for(QueryBuilder::class);
        $connectionMock = Double::for(Connection::class);
        $repo = $this->getRepository($connectionMock);
        $connectionMock->expects('table')->with('migrations')->returns($query);
        $query->expects('where')->with('migration', 'foo')->returns($query);
        $query->expects('delete');
        $query->expects('useWritePdo')->returns($query);
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
        $query = Double::for(QueryBuilder::class);
        $connectionMock = Double::for(Connection::class);
        $repo = $this->getRepository($connectionMock);
        $connectionMock->expects('table')->with('migrations')->returns($query);
        $query->expects('max')->returns(1);
        $query->expects('useWritePdo')->returns($query);

        $this->assertEquals(1, $repo->getLastBatchNumber());
    }

    public function testCreateRepositoryCreatesProperDatabaseTable()
    {
        $schema = Double::for(SchemaBuilder::class);
        $connectionMock = Double::for(Connection::class);
        $repo = $this->getRepository($connectionMock);
        $connectionMock->expects('getSchemaBuilder')->returns($schema);
        $schema->expects('create')->with('migrations', Argument::type(Closure::class));

        $repo->createRepository();
    }

    protected function getRepository($connection = null)
    {
        return new DatabaseMigrationRepository($this->resolver($connection), 'migrations');
    }

    protected function resolver($connection = null)
    {
        $resolver = new ConnectionResolver;
        $resolver->addConnection('default', $connection ?: Double::for(Connection::class));
        $resolver->setDefaultConnection('default');

        return $resolver;
    }
}
