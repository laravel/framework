<?php

namespace Illuminate\Tests\Database;

use Illuminate\Container\Container;
use Illuminate\Database\Connection;
use Illuminate\Database\Connectors\ConnectionFactory;
use Illuminate\Database\DatabaseManager;
use Mockery as m;
use PDO;
use PHPUnit\Framework\TestCase;

class DatabaseManagerTest extends TestCase
{
    public function testParseConnectionNameRecognizesDirectType()
    {
        $manager = new DatabaseManagerTestManager(new Container, m::mock(ConnectionFactory::class));

        $this->assertSame(['pgsql', 'direct'], $manager->parseConnectionNamePublic('pgsql::direct'));
        $this->assertSame(['pgsql', 'read'], $manager->parseConnectionNamePublic('pgsql::read'));
        $this->assertSame(['pgsql', null], $manager->parseConnectionNamePublic('pgsql'));
    }

    public function testSetPdoForDirectTypeSetsReadAndWritePdosToDirectPdo()
    {
        $manager = new DatabaseManagerTestManager(new Container, m::mock(ConnectionFactory::class));
        $connection = new Connection(new DatabaseManagerTestMockPDO);
        $directPdo = new DatabaseManagerTestMockPDO;

        $connection->setDirectPdo($directPdo);

        $manager->setPdoForTypePublic($connection, 'direct');

        $this->assertSame($directPdo, $connection->getPdo());
        $this->assertSame($directPdo, $connection->getReadPdo());
    }

    public function testRefreshPdoConnectionsRefreshesDirectPdo()
    {
        $manager = new DatabaseManagerTestManager(new Container, m::mock(ConnectionFactory::class));
        $connection = new Connection(new DatabaseManagerTestMockPDO, 'database', '', ['name' => 'pgsql']);
        $freshDirectPdo = new DatabaseManagerTestMockPDO;
        $freshConnection = new Connection(new DatabaseManagerTestMockPDO, 'database', '', ['name' => 'pgsql']);
        $freshConnection->setReadPdo(new DatabaseManagerTestMockPDO);
        $freshConnection->setDirectPdo($freshDirectPdo);
        $freshConnection->setDirectPdoConfig(['host' => 'direct-host']);

        $manager->freshConnection = $freshConnection;
        $manager->setCachedConnection('pgsql::direct', $connection);

        $manager->refreshPdoConnectionsPublic('pgsql::direct');

        $this->assertSame($freshDirectPdo, $connection->getPdo());
        $this->assertSame($freshDirectPdo, $connection->getReadPdo());
        $this->assertSame($freshDirectPdo, $connection->getDirectPdo());
    }
}

class DatabaseManagerTestManager extends DatabaseManager
{
    public $freshConnection;

    public function parseConnectionNamePublic($name)
    {
        return $this->parseConnectionName($name);
    }

    public function setPdoForTypePublic(Connection $connection, $type = null)
    {
        return $this->setPdoForType($connection, $type);
    }

    public function refreshPdoConnectionsPublic($name)
    {
        return $this->refreshPdoConnections($name);
    }

    public function setCachedConnection($name, Connection $connection)
    {
        $this->connections[$name] = $connection;
    }

    protected function makeConnection($name)
    {
        return $this->freshConnection;
    }
}

class DatabaseManagerTestMockPDO extends PDO
{
    public function __construct()
    {
        //
    }
}
