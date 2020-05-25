<?php

namespace Illuminate\Tests\Integration\Database\SqlServer;

use Illuminate\Database\DatabaseManager;
use Orchestra\Testbench\TestCase;

abstract class DatabaseSqlServerTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if(config('database.default') !== 'sqlsrv'){
            $this->markTestSkipped('Sql server only tested when enabled.');
            return;
        }
        $this->recreateDatabase();
    }

    protected function tearDown(): void
    {
        /** @var DatabaseManager $db */
        $db = app('db');
        $db->disconnect();
        parent::tearDown();
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('app.debug', 'true');
    }
    public function recreateDatabase()
    {
        $db = config('database.connections.sqlsrv.database', 'forge');
        $host = config('database.connections.sqlsrv.host', 'localhost');
        $port = config('database.connections.sqlsrv.port', '1433');
        $user = config('database.connections.sqlsrv.username');
        $pass = config('database.connections.sqlsrv.password');

        $serverName = "tcp:$host,$port";
        $conn = new \PDO("sqlsrv:server=$serverName ;", $user, $pass);
        $conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        // alter database first to drop all existing connections.
        // I cant seem to make laravel/php close all connections before setUp of new test somehow.
        $tsql = " If(db_id(N'$db') IS NOT NULL)
         BEGIN
            ALTER DATABASE [$db] SET SINGLE_USER WITH ROLLBACK IMMEDIATE;
            DROP DATABASE [$db];
         END
        CREATE DATABASE [$db];";

        $conn->query($tsql);
        $conn = null; // closing connection.
    }

}
