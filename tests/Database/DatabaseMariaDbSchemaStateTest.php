<?php

namespace Database;

use Generator;
use Illuminate\Database\MariaDbConnection;
use Illuminate\Database\Schema\MariaDbSchemaState;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class DatabaseMariaDbSchemaStateTest extends TestCase
{
    #[DataProvider('provider')]
    public function testConnectionString(string $expectedConnectionString, array $expectedVariables, array $dbConfig): void
    {
        $connection = $this->createMock(MariaDbConnection::class);
        $connection->method('getConfig')->willReturn($dbConfig);

        $schemaState = new MariaDbSchemaState($connection);

        // test connectionString
        $method = new ReflectionMethod(get_class($schemaState), 'connectionString');
        $connString = $method->invoke($schemaState);

        self::assertEquals($expectedConnectionString, $connString);

        // test baseVariables
        $method = new ReflectionMethod(get_class($schemaState), 'baseVariables');
        $variables = $method->invoke($schemaState, $dbConfig);

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
                'LARAVEL_LOAD_SSL_CERT' => '',
                'LARAVEL_LOAD_SSL_KEY' => '',
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
                'LARAVEL_LOAD_SSL_CERT' => '',
                'LARAVEL_LOAD_SSL_KEY' => '',
            ], [
                'username' => 'root',
                'database' => 'forge',
                'options' => [
                    PHP_VERSION_ID >= 80500 ? \Pdo\Mysql::ATTR_SSL_CA : \PDO::MYSQL_ATTR_SSL_CA => 'ssl.ca',
                ],
            ],
        ];

        yield 'ssl_cert_and_key' => [
            ' --user="${:LARAVEL_LOAD_USER}" --password="${:LARAVEL_LOAD_PASSWORD}" --host="${:LARAVEL_LOAD_HOST}" --port="${:LARAVEL_LOAD_PORT}" --ssl-ca="${:LARAVEL_LOAD_SSL_CA}" --ssl-cert="${:LARAVEL_LOAD_SSL_CERT}" --ssl-key="${:LARAVEL_LOAD_SSL_KEY}"', [
                'LARAVEL_LOAD_SOCKET' => '',
                'LARAVEL_LOAD_HOST' => '',
                'LARAVEL_LOAD_PORT' => '',
                'LARAVEL_LOAD_USER' => 'root',
                'LARAVEL_LOAD_PASSWORD' => '',
                'LARAVEL_LOAD_DATABASE' => 'forge',
                'LARAVEL_LOAD_SSL_CA' => 'ssl.ca',
                'LARAVEL_LOAD_SSL_CERT' => '/path/to/client-cert.pem',
                'LARAVEL_LOAD_SSL_KEY' => '/path/to/client-key.pem',
            ], [
                'username' => 'root',
                'database' => 'forge',
                'options' => [
                    PHP_VERSION_ID >= 80500 ? \Pdo\Mysql::ATTR_SSL_CA : \PDO::MYSQL_ATTR_SSL_CA => 'ssl.ca',
                    PHP_VERSION_ID >= 80500 ? \Pdo\Mysql::ATTR_SSL_CERT : \PDO::MYSQL_ATTR_SSL_CERT => '/path/to/client-cert.pem',
                    PHP_VERSION_ID >= 80500 ? \Pdo\Mysql::ATTR_SSL_KEY : \PDO::MYSQL_ATTR_SSL_KEY => '/path/to/client-key.pem',
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
                'LARAVEL_LOAD_SSL_CERT' => '',
                'LARAVEL_LOAD_SSL_KEY' => '',
            ], [
                'username' => 'root',
                'database' => 'forge',
                'unix_socket' => '/tmp/mysql.sock',
            ],
        ];
    }
}
