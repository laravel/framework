<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Connection;
use Illuminate\Database\Schema\PostgresSchemaState;
use PDO;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class DatabasePostgresSchemaStateTest extends TestCase
{
    public function testBaseVariablesUseConfiguredConnectionByDefault()
    {
        $schemaState = new PostgresSchemaState(new Connection(new DatabasePostgresSchemaStateTestMockPDO));

        $variables = (new ReflectionMethod(PostgresSchemaState::class, 'baseVariables'))->invoke($schemaState, [
            'host' => 'pooler-host',
            'port' => '6432',
            'username' => 'root',
            'password' => 'secret',
            'database' => 'laravel',
            'sslmode' => 'prefer',
        ]);

        $this->assertSame([
            'LARAVEL_LOAD_HOST' => 'pooler-host',
            'LARAVEL_LOAD_PORT' => '6432',
            'LARAVEL_LOAD_USER' => 'root',
            'PGPASSWORD' => 'secret',
            'PGSSLMODE' => 'prefer',
            'LARAVEL_LOAD_DATABASE' => 'laravel',
        ], $variables);
    }

    public function testBaseVariablesUseDirectConnectionConfigurationWhenAvailable()
    {
        $connection = new Connection(new DatabasePostgresSchemaStateTestMockPDO);
        $connection->setDirectPdoConfig([
            'host' => ['direct-host', 'direct-host-2'],
            'port' => '5432',
            'username' => 'direct_user',
            'password' => 'direct_secret',
            'database' => 'direct_database',
            'sslmode' => 'require',
        ]);

        $schemaState = new PostgresSchemaState($connection);

        $variables = (new ReflectionMethod(PostgresSchemaState::class, 'baseVariables'))->invoke($schemaState, [
            'host' => 'pooler-host',
            'port' => '6432',
            'username' => 'root',
            'password' => 'secret',
            'database' => 'laravel',
            'sslmode' => 'prefer',
        ]);

        $this->assertSame([
            'LARAVEL_LOAD_HOST' => 'direct-host',
            'LARAVEL_LOAD_PORT' => '5432',
            'LARAVEL_LOAD_USER' => 'direct_user',
            'PGPASSWORD' => 'direct_secret',
            'PGSSLMODE' => 'require',
            'LARAVEL_LOAD_DATABASE' => 'direct_database',
        ], $variables);
    }

    public function testBaseVariablesDoNotExportEmptySslMode()
    {
        $schemaState = new PostgresSchemaState(new Connection(new DatabasePostgresSchemaStateTestMockPDO));

        $variables = (new ReflectionMethod(PostgresSchemaState::class, 'baseVariables'))->invoke($schemaState, [
            'host' => 'pooler-host',
            'port' => '6432',
            'username' => 'root',
            'password' => 'secret',
            'database' => 'laravel',
        ]);

        $this->assertSame([
            'LARAVEL_LOAD_HOST' => 'pooler-host',
            'LARAVEL_LOAD_PORT' => '6432',
            'LARAVEL_LOAD_USER' => 'root',
            'PGPASSWORD' => 'secret',
            'LARAVEL_LOAD_DATABASE' => 'laravel',
        ], $variables);
    }
}

class DatabasePostgresSchemaStateTestMockPDO extends PDO
{
    public function __construct()
    {
        //
    }
}
