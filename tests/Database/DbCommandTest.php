<?php

namespace Illuminate\Tests\Database;

use Illuminate\Database\Console\DbCommand;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class DbCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testBuildDatabaseUrlForMysql()
    {
        $command = new DbCommandTestStub();

        $connection = [
            'driver' => 'mysql',
            'host' => 'localhost',
            'port' => 3306,
            'database' => 'forge',
            'username' => 'root',
            'password' => 'secret',
        ];

        $url = $command->buildDatabaseUrl($connection);

        $this->assertSame('mysql://root:secret@localhost:3306/forge', $url);
    }

    public function testBuildDatabaseUrlForPostgresql()
    {
        $command = new DbCommandTestStub();

        $connection = [
            'driver' => 'pgsql',
            'host' => 'ep-falling-heart-a5luqcqc.aws-us-east-2.pg.laravel.cloud',
            'port' => 5432,
            'database' => 'main',
            'username' => 'laravel',
            'password' => 'npg_G7UguAkROK4l',
        ];

        $url = $command->buildDatabaseUrl($connection);

        $this->assertSame(
            'postgresql://laravel:npg_G7UguAkROK4l@ep-falling-heart-a5luqcqc.aws-us-east-2.pg.laravel.cloud:5432/main',
            $url
        );
    }

    public function testBuildDatabaseUrlForSqlite()
    {
        $command = new DbCommandTestStub();

        $connection = [
            'driver' => 'sqlite',
            'database' => '/path/to/database.sqlite',
        ];

        $url = $command->buildDatabaseUrl($connection);

        $this->assertSame('/path/to/database.sqlite', $url);
    }

    public function testBuildDatabaseUrlForSqlServer()
    {
        $command = new DbCommandTestStub();

        $connection = [
            'driver' => 'sqlsrv',
            'host' => 'localhost',
            'port' => 1433,
            'database' => 'master',
            'username' => 'sa',
            'password' => 'Password123!',
        ];

        $url = $command->buildDatabaseUrl($connection);

        $this->assertSame('sqlserver://sa:Password123!@localhost:1433/master', $url);
    }

    public function testBuildDatabaseUrlWithSpecialCharactersInPassword()
    {
        $command = new DbCommandTestStub();

        $connection = [
            'driver' => 'mysql',
            'host' => 'localhost',
            'port' => 3306,
            'database' => 'forge',
            'username' => 'root',
            'password' => 'p@ss:word!#$',
        ];

        $url = $command->buildDatabaseUrl($connection);

        $this->assertSame('mysql://root:p%40ss:word!%23$@localhost:3306/forge', $url);
    }

    public function testBuildDatabaseUrlWithoutPassword()
    {
        $command = new DbCommandTestStub();

        $connection = [
            'driver' => 'mysql',
            'host' => 'localhost',
            'port' => 3306,
            'database' => 'forge',
            'username' => 'root',
            'password' => '',
        ];

        $url = $command->buildDatabaseUrl($connection);

        $this->assertSame('mysql://root@localhost:3306/forge', $url);
    }

    public function testBuildDatabaseUrlWithoutPort()
    {
        $command = new DbCommandTestStub();

        $connection = [
            'driver' => 'mysql',
            'host' => 'localhost',
            'database' => 'forge',
            'username' => 'root',
            'password' => 'secret',
        ];

        $url = $command->buildDatabaseUrl($connection);

        $this->assertSame('mysql://root:secret@localhost/forge', $url);
    }

    public function testBuildDatabaseUrlWithSslMode()
    {
        $command = new DbCommandTestStub();

        $connection = [
            'driver' => 'pgsql',
            'host' => 'localhost',
            'port' => 5432,
            'database' => 'forge',
            'username' => 'postgres',
            'password' => 'secret',
            'sslmode' => 'require',
        ];

        $url = $command->buildDatabaseUrl($connection);

        $this->assertSame('postgresql://postgres:secret@localhost:5432/forge?sslmode=require', $url);
    }

    public function testGetDriverScheme()
    {
        $command = new DbCommandTestStub();

        $this->assertSame('mysql', $command->getDriverScheme('mysql'));
        $this->assertSame('mysql', $command->getDriverScheme('mariadb'));
        $this->assertSame('postgresql', $command->getDriverScheme('pgsql'));
        $this->assertSame('sqlite', $command->getDriverScheme('sqlite'));
        $this->assertSame('sqlserver', $command->getDriverScheme('sqlsrv'));
    }

    public function testBuildDatabaseUrlForMariaDb()
    {
        $command = new DbCommandTestStub();

        $connection = [
            'driver' => 'mariadb',
            'host' => 'localhost',
            'port' => 3306,
            'database' => 'myapp',
            'username' => 'root',
            'password' => 'password',
        ];

        $url = $command->buildDatabaseUrl($connection);

        // MariaDB uses the same mysql:// scheme
        $this->assertSame('mysql://root:password@localhost:3306/myapp', $url);
    }
}

class DbCommandTestStub extends DbCommand
{
    public function buildDatabaseUrl(array $connection)
    {
        return parent::buildDatabaseUrl($connection);
    }

    public function getDriverScheme($driver)
    {
        return parent::getDriverScheme($driver);
    }
}
