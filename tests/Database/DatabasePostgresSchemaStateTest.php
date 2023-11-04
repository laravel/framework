<?php

namespace Illuminate\Tests\Database;

use Generator;
use Illuminate\Database\PostgresConnection;
use Illuminate\Database\Schema\PostgresSchemaState;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class DatabasePostgresSchemaStateTest extends TestCase
{
    /**
     * @dataProvider provider
     */
    public function testConnectionString(string $expectedBaseDumpCommand, string $expectedLoadCommand, array $expectedVariables, array $dbConfig): void
    {
        $connection = $this->createMock(PostgresConnection::class);
        $connection->method('getConfig')->willReturn($dbConfig);

        $latestCommand = null;

        $schemaState = new PostgresSchemaState($connection, processFactory: function (...$arguments) use (&$latestCommand) {
            $latestCommand = $arguments[0];

            return new class
            {
                public function __call(string $name, $arguments)
                {
                    return $this;
                }
            };
        });

        // test baseDumpCommand
        $method = new ReflectionMethod(get_class($schemaState), 'baseDumpCommand');
        $baseDumpCommand = $method->invoke($schemaState);

        self::assertEquals($expectedBaseDumpCommand, $baseDumpCommand);

        // test load
        $method = new ReflectionMethod(get_class($schemaState), 'load');
        $method->invoke($schemaState, 'PATH');

        self::assertEquals($expectedLoadCommand, $latestCommand);

        // test baseVariables
        $method = new ReflectionMethod(get_class($schemaState), 'baseVariables');
        $variables = $method->invoke($schemaState, $dbConfig);

        self::assertEquals($expectedVariables, $variables);
    }

    public static function provider(): Generator
    {
        yield 'default' => [
            'pg_dump --no-owner --no-acl --host="${:LARAVEL_LOAD_HOST}" --port="${:LARAVEL_LOAD_PORT}" --username="${:LARAVEL_LOAD_USER}" --dbname="${:LARAVEL_LOAD_DATABASE}"',
            'pg_restore --no-owner --no-acl --clean --if-exists --host="${:LARAVEL_LOAD_HOST}" --port="${:LARAVEL_LOAD_PORT}" --username="${:LARAVEL_LOAD_USER}" --dbname="${:LARAVEL_LOAD_DATABASE}" "${:LARAVEL_LOAD_PATH}"',
            [
                'LARAVEL_LOAD_HOST' => '127.0.0.1',
                'LARAVEL_LOAD_PORT' => '',
                'LARAVEL_LOAD_USER' => 'root',
                'LARAVEL_LOAD_DATABASE' => 'forge',
                'PGPASSWORD' => 'secret',
            ], [
                'username' => 'root',
                'host' => '127.0.0.1',
                'database' => 'forge',
                'password' => 'secret',
            ],
        ];

        yield 'default_bin_path' => [
            '/Users/Shared/DBngin/postgresql/15.1/bin/pg_dump --no-owner --no-acl --host="${:LARAVEL_LOAD_HOST}" --port="${:LARAVEL_LOAD_PORT}" --username="${:LARAVEL_LOAD_USER}" --dbname="${:LARAVEL_LOAD_DATABASE}"',
            '/Users/Shared/DBngin/postgresql/15.1/bin/pg_restore --no-owner --no-acl --clean --if-exists --host="${:LARAVEL_LOAD_HOST}" --port="${:LARAVEL_LOAD_PORT}" --username="${:LARAVEL_LOAD_USER}" --dbname="${:LARAVEL_LOAD_DATABASE}" "${:LARAVEL_LOAD_PATH}"',
            [
                'LARAVEL_LOAD_HOST' => '127.0.0.1',
                'LARAVEL_LOAD_PORT' => '',
                'LARAVEL_LOAD_USER' => 'root',
                'LARAVEL_LOAD_DATABASE' => 'forge',
                'PGPASSWORD' => 'secret',
            ], [
                'username' => 'root',
                'host' => '127.0.0.1',
                'database' => 'forge',
                'password' => 'secret',
                'bin' => '/Users/Shared/DBngin/postgresql/15.1/bin',
            ],
        ];
    }
}
