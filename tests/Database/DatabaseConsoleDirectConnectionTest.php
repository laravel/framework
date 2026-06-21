<?php

namespace Illuminate\Tests\Database;

use Illuminate\Config\Repository as Config;
use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Console\Concerns\InteractsWithPooledConnections;
use Illuminate\Database\Console\DbCommand;
use Illuminate\Foundation\Application;
use Mockery as m;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;

class DatabaseConsoleDirectConnectionTest extends TestCase
{
    public function testInteractsWithPooledConnectionsUsesDirectVariantWhenConfigured()
    {
        $resolver = m::mock(ConnectionResolverInterface::class);
        $baseConnection = m::mock(Connection::class);
        $directConnection = m::mock(Connection::class);
        $command = new DatabaseConsoleDirectConnectionTestCommand;

        $resolver->shouldReceive('getDefaultConnection')->once()->andReturn('pgsql');
        $resolver->shouldReceive('connection')->once()->with('pgsql')->andReturn($baseConnection);
        $baseConnection->shouldReceive('hasDirectConnection')->once()->andReturn(true);
        $resolver->shouldReceive('connection')->once()->with('pgsql::direct')->andReturn($directConnection);

        $this->assertSame($directConnection, $command->resolve($resolver, null));
    }

    public function testInteractsWithPooledConnectionsPassesThroughWhenNoDirectVariantIsConfigured()
    {
        $resolver = m::mock(ConnectionResolverInterface::class);
        $connection = m::mock(Connection::class);
        $command = new DatabaseConsoleDirectConnectionTestCommand;

        $resolver->shouldReceive('connection')->once()->with('sqlite')->andReturn($connection);
        $connection->shouldReceive('hasDirectConnection')->once()->andReturn(false);

        $this->assertSame($connection, $command->resolve($resolver, 'sqlite'));
    }

    public function testInteractsWithPooledConnectionsPassesThroughExplicitSuffixes()
    {
        $resolver = m::mock(ConnectionResolverInterface::class);
        $connection = m::mock(Connection::class);
        $command = new DatabaseConsoleDirectConnectionTestCommand;

        $resolver->shouldReceive('connection')->once()->with('pgsql::write')->andReturn($connection);
        $connection->shouldReceive('hasDirectConnection')->once()->andReturn(true);

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
