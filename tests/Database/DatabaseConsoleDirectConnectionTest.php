<?php

namespace Illuminate\Tests\Database;

use Illuminate\Config\Repository as Config;
use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionResolver;
use Illuminate\Database\Console\Concerns\InteractsWithPooledConnections;
use Illuminate\Database\Console\DbCommand;
use Illuminate\Foundation\Application;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;

class DatabaseConsoleDirectConnectionTest extends TestCase
{
    public function testInteractsWithPooledConnectionsUsesDirectVariantWhenConfigured()
    {
        $resolver = new ConnectionResolver([
            'pgsql' => new DatabaseConsoleDirectConnectionTestConnection(true),
            'pgsql::direct' => $directConnection = new DatabaseConsoleDirectConnectionTestConnection(false),
        ]);
        $resolver->setDefaultConnection('pgsql');
        $command = new DatabaseConsoleDirectConnectionTestCommand;

        $this->assertSame($directConnection, $command->resolve($resolver, null));
    }

    public function testInteractsWithPooledConnectionsPassesThroughWhenNoDirectVariantIsConfigured()
    {
        $resolver = new ConnectionResolver([
            'sqlite' => $connection = new DatabaseConsoleDirectConnectionTestConnection(false),
        ]);
        $command = new DatabaseConsoleDirectConnectionTestCommand;

        $this->assertSame($connection, $command->resolve($resolver, 'sqlite'));
    }

    public function testInteractsWithPooledConnectionsPassesThroughExplicitSuffixes()
    {
        $resolver = new ConnectionResolver([
            'pgsql::write' => $connection = new DatabaseConsoleDirectConnectionTestConnection(true),
        ]);
        $command = new DatabaseConsoleDirectConnectionTestCommand;

        $this->assertSame($connection, $command->resolve($resolver, 'pgsql::write'));
    }

    public function testDbCommandUsesBasePostgresConnectionWhenDirectEndpointExistsWithoutPooledMode()
    {
        $connection = $this->dbCommand()->getConnection();

        $this->assertSame('pooler-host', $connection['host']);
        $this->assertSame('6432', $connection['port']);
        $this->assertArrayHasKey('direct', $connection);
    }

    public function testDbCommandDefaultsToDirectPostgresConnectionWhenPooledModeIsEnabled()
    {
        $connection = $this->dbCommand(pooled: true)->getConnection();

        $this->assertSame('direct-host', $connection['host']);
        $this->assertSame('5432', $connection['port']);
        $this->assertSame('direct-user', $connection['username']);
        $this->assertSame('direct-password', $connection['password']);
        $this->assertSame('require', $connection['sslmode']);
        $this->assertSame('laravel', $connection['database']);
        $this->assertArrayNotHasKey('direct', $connection);
    }

    public function testDbCommandPooledOptionUsesBasePooledConnection()
    {
        $connection = $this->dbCommand(['--pooled' => true])->getConnection();

        $this->assertSame('pooler-host', $connection['host']);
        $this->assertSame('6432', $connection['port']);
    }

    public function testDbCommandReadAndWriteOptionsUsePooledConnectionBranches()
    {
        $readConnection = $this->dbCommand(['--read' => true], pooled: true)->getConnection();
        $writeConnection = $this->dbCommand(['--write' => true], pooled: true)->getConnection();

        $this->assertSame('read-pooler-host', $readConnection['host']);
        $this->assertSame('6433', $readConnection['port']);
        $this->assertSame('write-pooler-host', $writeConnection['host']);
        $this->assertSame('6434', $writeConnection['port']);
    }

    protected function dbCommand(array $input = [], bool $pooled = false)
    {
        $command = new DbCommand;
        $command->setLaravel($this->application($pooled));
        $command->setInput(new ArrayInput($input, $command->getDefinition()));

        return $command;
    }

    protected function application(bool $pooled = false)
    {
        $app = new Application;
        $app->instance('config', new Config([
            'database' => [
                'default' => 'pgsql',
                'connections' => [
                    'pgsql' => [
                        'driver' => 'pgsql',
                        'host' => 'pooler-host',
                        'port' => '6432',
                        'database' => 'laravel',
                        'username' => 'root',
                        'password' => '',
                        'pooled' => $pooled,
                        'read' => [
                            'host' => ['read-pooler-host', 'read-pooler-host-2'],
                            'port' => '6433',
                        ],
                        'write' => [
                            'host' => 'write-pooler-host',
                            'port' => '6434',
                        ],
                        'direct' => [
                            'host' => ['direct-host', 'direct-host-2'],
                            'port' => '5432',
                            'username' => 'direct-user',
                            'password' => 'direct-password',
                            'sslmode' => 'require',
                        ],
                    ],
                ],
            ],
        ]));

        return $app;
    }
}

class DatabaseConsoleDirectConnectionTestCommand
{
    use InteractsWithPooledConnections;

    public function resolve($connections, $database)
    {
        return $this->resolveDirectConnectionIfPossible($connections, $database);
    }
}

class DatabaseConsoleDirectConnectionTestConnection extends Connection
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
