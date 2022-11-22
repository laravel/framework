<?php

namespace Illuminate\Tests\Database;

use Generator;
use Illuminate\Database\MySqlConnection;
use Illuminate\Database\Schema\MySqlSchemaState;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class DatabaseMySqlSchemaStateTest extends TestCase
{
    /**
     * @dataProvider provider
     */
    public function testConnectionString(string $expectedConnectionString, array $expectedVariables, array $dbConfig): void
    {
        $connection = $this->createMock(MySqlConnection::class);
        $connection->method('getConfig')->willReturn($dbConfig);

        $schemaState = new MySqlSchemaState($connection);

        // test connectionString
        $method = new ReflectionMethod(get_class($schemaState), 'connectionString');
        $connString = tap($method)->setAccessible(true)->invoke($schemaState);

        self::assertEquals($expectedConnectionString, $connString);

        // test baseVariables
        $method = new ReflectionMethod(get_class($schemaState), 'baseVariables');
        $variables = tap($method)->setAccessible(true)->invoke($schemaState, $dbConfig);

        self::assertEquals($expectedVariables, $variables);
    }

    public static function provider(): Generator
    {
        yield 'default' => [
            ' --user="${:LARAVEL_LOAD_USER}" --password="${:LARAVEL_LOAD_PASSWORD}" --host="${:LARAVEL_LOAD_HOST}" --port="${:LARAVEL_LOAD_PORT}"', [
                'LARAVEL_LOAD_SOCKET' => '',
                'LARAVEL_LOAD_HOST' => '127.0.0.1',
                'LARAVEL_LOAD_PORT' => '',
                'LARAVEL_LOAD_USER' => 'root',
                'LARAVEL_LOAD_PASSWORD' => '',
                'LARAVEL_LOAD_DATABASE' => 'forge',
                'LARAVEL_LOAD_SSL_CA' => '',
            ], [
                'username' => 'root',
                'host' => '127.0.0.1',
                'database' => 'forge',
            ],
        ];

        yield 'ssl_ca' => [
            ' --user="${:LARAVEL_LOAD_USER}" --password="${:LARAVEL_LOAD_PASSWORD}" --host="${:LARAVEL_LOAD_HOST}" --port="${:LARAVEL_LOAD_PORT}" --ssl-ca="${:LARAVEL_LOAD_SSL_CA}"', [
                'LARAVEL_LOAD_SOCKET' => '',
                'LARAVEL_LOAD_HOST' => '',
                'LARAVEL_LOAD_PORT' => '',
                'LARAVEL_LOAD_USER' => 'root',
                'LARAVEL_LOAD_PASSWORD' => '',
                'LARAVEL_LOAD_DATABASE' => 'forge',
                'LARAVEL_LOAD_SSL_CA' => 'ssl.ca',
            ], [
                'username' => 'root',
                'database' => 'forge',
                'options' => [
                    \PDO::MYSQL_ATTR_SSL_CA => 'ssl.ca',
                ],
            ],
        ];

        yield 'unix socket' => [
            ' --user="${:LARAVEL_LOAD_USER}" --password="${:LARAVEL_LOAD_PASSWORD}" --socket="${:LARAVEL_LOAD_SOCKET}"', [
                'LARAVEL_LOAD_SOCKET' => '/tmp/mysql.sock',
                'LARAVEL_LOAD_HOST' => '',
                'LARAVEL_LOAD_PORT' => '',
                'LARAVEL_LOAD_USER' => 'root',
                'LARAVEL_LOAD_PASSWORD' => '',
                'LARAVEL_LOAD_DATABASE' => 'forge',
                'LARAVEL_LOAD_SSL_CA' => '',
            ], [
                'username' => 'root',
                'database' => 'forge',
                'unix_socket' => '/tmp/mysql.sock',
            ],
        ];
    }
}
