<?php

namespace Illuminate\Tests\Database;

use PHPUnit\Framework\TestCase;
use Illuminate\Database\UrlParser;

class DatabaseUrlParserTest extends TestCase
{
    /**
     * @dataProvider databaseUrls
     */
    public function testDatabaseUrlsAreParsed($config, $expectedOutput)
    {
        $this->assertEquals($expectedOutput, (new UrlParser)->parseDatabaseConfigWithUrl($config));
    }

    public function testDriversAliases()
    {
        $this->assertEquals([
            'mssql' => 'sqlsrv',
            'mysql2' => 'mysql',
            'postgres' => 'pgsql',
            'postgresql' => 'pgsql',
            'sqlite3' => 'sqlite',
        ], UrlParser::getDriverAliases());

        UrlParser::addDriverAlias('some-particular-alias', 'mysql');

        $this->assertEquals([
            'mssql' => 'sqlsrv',
            'mysql2' => 'mysql',
            'postgres' => 'pgsql',
            'postgresql' => 'pgsql',
            'sqlite3' => 'sqlite',
            'some-particular-alias' => 'mysql',
        ], UrlParser::getDriverAliases());

        $this->assertEquals([
            'driver' => 'mysql',
        ], (new UrlParser)->parseDatabaseConfigWithUrl('some-particular-alias://null'));
    }

    public function databaseUrls()
    {
        return [
            'simple URL' => [
                'mysql://foo:bar@localhost/baz',
                [
                    'driver' => 'mysql',
                    'username' => 'foo',
                    'password' => 'bar',
                    'host' => 'localhost',
                    'database' => 'baz',
                ],
            ],
            'simple URL with port' => [
                'mysql://foo:bar@localhost:134/baz',
                [
                    'driver' => 'mysql',
                    'username' => 'foo',
                    'password' => 'bar',
                    'host' => 'localhost',
                    'port' => 134,
                    'database' => 'baz',
                ],
            ],
            'sqlite relative URL with host' => [
                'sqlite://localhost/foo/database.sqlite',
                [
                    'database' => 'foo/database.sqlite',
                    'driver' => 'sqlite',
                    'host' => 'localhost',
                ],
            ],
            'sqlite absolute URL with host' => [
                'sqlite://localhost//tmp/database.sqlite',
                [
                    'database' => '/tmp/database.sqlite',
                    'driver' => 'sqlite',
                    'host' => 'localhost',
                ],
            ],
            'sqlite relative URL without host' => [
                'sqlite:///foo/database.sqlite',
                [
                    'database' => 'foo/database.sqlite',
                    'driver' => 'sqlite',
                ],
            ],
            'sqlite absolute URL without host' => [
                'sqlite:////tmp/database.sqlite',
                [
                    'database' => '/tmp/database.sqlite',
                    'driver' => 'sqlite',
                ],
            ],
            'sqlite memory' => [
                'sqlite:///:memory:',
                [
                    'database' => ':memory:',
                    'driver' => 'sqlite',
                ],
            ],
            'params parsed from URL override individual params' => [
                [
                    'url' => 'mysql://foo:bar@localhost/baz',
                    'password' => 'lulz',
                    'driver' => 'sqlite',
                ],
                [
                    'username' => 'foo',
                    'password' => 'bar',
                    'host' => 'localhost',
                    'database' => 'baz',
                    'driver' => 'mysql',
                ],
            ],
            'params not parsed from URL but individual params are preserved' => [
                [
                    'url' => 'mysql://foo:bar@localhost/baz',
                    'port' => 134,
                ],
                [
                    'username' => 'foo',
                    'password' => 'bar',
                    'host' => 'localhost',
                    'port' => 134,
                    'database' => 'baz',
                    'driver' => 'mysql',
                ],
            ],
            'query params from URL are used as extra params' => [
                'url' => 'mysql://foo:bar@localhost/database?charset=UTF-8',
                [
                    'driver' => 'mysql',
                    'database' => 'database',
                    'host' => 'localhost',
                    'username' => 'foo',
                    'password' => 'bar',
                    'charset' => 'UTF-8',
                ],
            ],
            'simple URL with driver set apart' => [
                [
                    'url' => '//foo:bar@localhost/baz',
                    'driver' => 'sqlsrv',
                ],
                [
                    'username' => 'foo',
                    'password' => 'bar',
                    'host' => 'localhost',
                    'database' => 'baz',
                    'driver' => 'sqlsrv',
                ],
            ],
            'simple URL with percent encoding' => [
                'mysql://foo%3A:bar%2F@localhost/baz+baz%40',
                [
                    'username' => 'foo:',
                    'password' => 'bar/',
                    'host' => 'localhost',
                    'database' => 'baz+baz@',
                    'driver' => 'mysql',
                ],
            ],
            'simple URL with percent sign in password' => [
                'mysql://foo:bar%25bar@localhost/baz',
                [
                    'username' => 'foo',
                    'password' => 'bar%bar',
                    'host' => 'localhost',
                    'database' => 'baz',
                    'driver' => 'mysql',
                ],
            ],
            'URL with mssql alias driver' => [
                'mssql://null',
                [
                    'driver' => 'sqlsrv',
                ],
            ],
            'URL with sqlsrv alias driver' => [
                'sqlsrv://null',
                [
                    'driver' => 'sqlsrv',
                ],
            ],
            'URL with mysql alias driver' => [
                'mysql://null',
                [
                    'driver' => 'mysql',
                ],
            ],
            'URL with mysql2 alias driver' => [
                'mysql2://null',
                [
                    'driver' => 'mysql',
                ],
            ],
            'URL with postgres alias driver' => [
                'postgres://null',
                [
                    'driver' => 'pgsql',
                ],
            ],
            'URL with postgresql alias driver' => [
                'postgresql://null',
                [
                    'driver' => 'pgsql',
                ],
            ],
            'URL with pgsql alias driver' => [
                'pgsql://null',
                [
                    'driver' => 'pgsql',
                ],
            ],
            'URL with sqlite alias driver' => [
                'sqlite://null',
                [
                    'driver' => 'sqlite',
                ],
            ],
            'URL with sqlite3 alias driver' => [
                'sqlite3://null',
                [
                    'driver' => 'sqlite',
                ],
            ],

            'URL with unknown driver' => [
                'foo://null',
                [
                    'driver' => 'foo',
                ],
            ],
            'Sqlite with foreign_key_constraints' => [
                'sqlite:////absolute/path/to/database.sqlite?foreign_key_constraints=true',
                [
                    'driver' => 'sqlite',
                    'database' => '/absolute/path/to/database.sqlite',
                    'foreign_key_constraints' => true,
                ],
            ],

            'Most complex example with read and write subarrays all in string' => [
                'mysql://root:@null/database?read[host][]=192.168.1.1&write[host][]=196.168.1.2&sticky=true&charset=utf8mb4&collation=utf8mb4_unicode_ci&prefix=',
                [
                    'read' => [
                        'host' => ['192.168.1.1'],
                    ],
                    'write' => [
                        'host' => ['196.168.1.2'],
                    ],
                    'sticky' => true,
                    'driver' => 'mysql',
                    'database' => 'database',
                    'username' => 'root',
                    'password' => '',
                    'charset' => 'utf8mb4',
                    'collation' => 'utf8mb4_unicode_ci',
                    'prefix' => '',
                ],
            ],

            'Full example from doc that prove that there isn\'t any Breaking Change' => [
                [
                    'driver' => 'mysql',
                    'host' => '127.0.0.1',
                    'port' => '3306',
                    'database' => 'forge',
                    'username' => 'forge',
                    'password' => '',
                    'unix_socket' => '',
                    'charset' => 'utf8mb4',
                    'collation' => 'utf8mb4_unicode_ci',
                    'prefix' => '',
                    'prefix_indexes' => true,
                    'strict' => true,
                    'engine' => null,
                    'options' => ['foo' => 'bar'],
                ],
                [
                    'driver' => 'mysql',
                    'host' => '127.0.0.1',
                    'port' => '3306',
                    'database' => 'forge',
                    'username' => 'forge',
                    'password' => '',
                    'unix_socket' => '',
                    'charset' => 'utf8mb4',
                    'collation' => 'utf8mb4_unicode_ci',
                    'prefix' => '',
                    'prefix_indexes' => true,
                    'strict' => true,
                    'engine' => null,
                    'options' => ['foo' => 'bar'],
                ],
            ],

            'Full example from doc with url overwriting parameters' => [
                [
                    'url' => 'pgsql://root:pass@db/local',
                    'driver' => 'mysql',
                    'host' => '127.0.0.1',
                    'port' => '3306',
                    'database' => 'forge',
                    'username' => 'forge',
                    'password' => '',
                    'unix_socket' => '',
                    'charset' => 'utf8mb4',
                    'collation' => 'utf8mb4_unicode_ci',
                    'prefix' => '',
                    'prefix_indexes' => true,
                    'strict' => true,
                    'engine' => null,
                    'options' => ['foo' => 'bar'],
                ],
                [
                    'driver' => 'pgsql',
                    'host' => 'db',
                    'port' => '3306',
                    'database' => 'local',
                    'username' => 'root',
                    'password' => 'pass',
                    'unix_socket' => '',
                    'charset' => 'utf8mb4',
                    'collation' => 'utf8mb4_unicode_ci',
                    'prefix' => '',
                    'prefix_indexes' => true,
                    'strict' => true,
                    'engine' => null,
                    'options' => ['foo' => 'bar'],
                ],
            ],
        ];
    }
}
