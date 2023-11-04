<?php

namespace Illuminate\Tests\Database;

use Generator;
use Illuminate\Database\MySqlConnection;
use Illuminate\Database\Schema\MySqlSchemaState;
use Illuminate\Support\Str;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class DatabaseMySqlSchemaStateTest extends TestCase
{
    /**
     * @dataProvider provider
     */
    public function testConnectionString(string $expectedConnectionString, string $expectedBaseDumpCommand, string $expectedLoadCommand, array $expectedVariables, array $dbConfig): void
    {
        $connection = $this->createMock(MySqlConnection::class);
        $connection->method('getConfig')->willReturn($dbConfig);
		
		$latestCommand = null;

        $schemaState = new MySqlSchemaState($connection, processFactory: function(...$arguments) use (&$latestCommand) {
			$latestCommand = $arguments[0];
			
			return new class
			{
				public function __call(string $name, $arguments)
				{
					 return $this;
				}
			};
        });
		
        // test connectionString
        $method = new ReflectionMethod(get_class($schemaState), 'connectionString');
        $connString = $method->invoke($schemaState);

        self::assertEquals($expectedConnectionString, $connString);
	    
	    // test baseDumpCommand
	    $method = new ReflectionMethod(get_class($schemaState), 'baseDumpCommand');
	    $baseDumpCommand = $method->invoke($schemaState);
	    
	    self::assertEquals(Str::replace('{{CONNECTION_STRING}}', $connString, $expectedBaseDumpCommand), $baseDumpCommand);
	    
	    // test load
	    $method = new ReflectionMethod(get_class($schemaState), 'load');
	    $method->invoke($schemaState, 'PATH');
	    
	    self::assertEquals(Str::replace('{{CONNECTION_STRING}}', $connString, $expectedLoadCommand), $latestCommand);

        // test baseVariables
        $method = new ReflectionMethod(get_class($schemaState), 'baseVariables');
        $variables = $method->invoke($schemaState, $dbConfig);

        self::assertEquals($expectedVariables, $variables);
    }

    public static function provider(): Generator
    {
        yield 'default' => [
            ' --user="${:LARAVEL_LOAD_USER}" --password="${:LARAVEL_LOAD_PASSWORD}" --host="${:LARAVEL_LOAD_HOST}" --port="${:LARAVEL_LOAD_PORT}"',
            'mysqldump {{CONNECTION_STRING}} --no-tablespaces --skip-add-locks --skip-comments --skip-set-charset --tz-utc --column-statistics=0 --set-gtid-purged=OFF "${:LARAVEL_LOAD_DATABASE}"',
            'mysql {{CONNECTION_STRING}} --database="${:LARAVEL_LOAD_DATABASE}" < "${:LARAVEL_LOAD_PATH}"',
            [
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
		
        yield 'default_bin_path' => [
            ' --user="${:LARAVEL_LOAD_USER}" --password="${:LARAVEL_LOAD_PASSWORD}" --host="${:LARAVEL_LOAD_HOST}" --port="${:LARAVEL_LOAD_PORT}"',
            '/Users/Shared/DBngin/MySQL/8.0.33/bin/mysqldump {{CONNECTION_STRING}} --no-tablespaces --skip-add-locks --skip-comments --skip-set-charset --tz-utc --column-statistics=0 --set-gtid-purged=OFF "${:LARAVEL_LOAD_DATABASE}"',
            '/Users/Shared/DBngin/MySQL/8.0.33/bin/mysql {{CONNECTION_STRING}} --database="${:LARAVEL_LOAD_DATABASE}" < "${:LARAVEL_LOAD_PATH}"',
            [
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
                'bin' => '/Users/Shared/DBngin/MySQL/8.0.33/bin'
            ],
        ];

        yield 'ssl_ca' => [
            ' --user="${:LARAVEL_LOAD_USER}" --password="${:LARAVEL_LOAD_PASSWORD}" --host="${:LARAVEL_LOAD_HOST}" --port="${:LARAVEL_LOAD_PORT}" --ssl-ca="${:LARAVEL_LOAD_SSL_CA}"',
            'mysqldump {{CONNECTION_STRING}} --no-tablespaces --skip-add-locks --skip-comments --skip-set-charset --tz-utc --column-statistics=0 --set-gtid-purged=OFF "${:LARAVEL_LOAD_DATABASE}"',
            'mysql {{CONNECTION_STRING}} --database="${:LARAVEL_LOAD_DATABASE}" < "${:LARAVEL_LOAD_PATH}"',
            [
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
            ' --user="${:LARAVEL_LOAD_USER}" --password="${:LARAVEL_LOAD_PASSWORD}" --socket="${:LARAVEL_LOAD_SOCKET}"',
            'mysqldump {{CONNECTION_STRING}} --no-tablespaces --skip-add-locks --skip-comments --skip-set-charset --tz-utc --column-statistics=0 --set-gtid-purged=OFF "${:LARAVEL_LOAD_DATABASE}"',
            'mysql {{CONNECTION_STRING}} --database="${:LARAVEL_LOAD_DATABASE}" < "${:LARAVEL_LOAD_PATH}"',
            [
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
