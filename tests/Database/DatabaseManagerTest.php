<?php

namespace Illuminate\Tests\Database;

use PHPUnit\Framework\TestCase;
use Illuminate\Database\Connection;
use Illuminate\Foundation\Application;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Connectors\ConnectionFactory;

class DatabaseManagerTest extends TestCase
{
    public function testMakeConnectionUsingExtensionWithoutHavingPredefinedConnectionName()
    {
        $connectionName = 'connection-name';
        $connection = $this->createMock(Connection::class);
        $app = $this->createMock(Application::class);
        $connectionFactory = $this->createMock(connectionFactory::class);

        $databaseManager = new DatabaseManager($app, $connectionFactory);

        $databaseManager->extend($connectionName, function () use ($connection) {
            return $connection;
        });

        $this->assertSame($connection, $databaseManager->connection($connectionName));
    }
}
