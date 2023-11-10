<?php

namespace Illuminate\Tests\Testing\Concerns;

use Illuminate\Config\Repository as Config;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\Concerns\TestDatabases;
use Mockery as m;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class TestDatabasesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Container::setInstance($container = new Container);

        $container->singleton('config', function () {
            return m::mock(Config::class)
                ->shouldReceive('get')
                ->once()
                ->with('database.default', null)
                ->andReturn('mysql')
                ->getMock();
        });

        $_SERVER['LARAVEL_PARALLEL_TESTING'] = 1;
    }

    public function testSwitchToDatabaseWithoutUrl()
    {
        DB::shouldReceive('purge')->once();

        config()->shouldReceive('get')
            ->once()
            ->with('database.connections.mysql.url', false)
            ->andReturn(false);

        config()->shouldReceive('set')
            ->once()
            ->with('database.connections.mysql.database', 'my_database_test_1');

        $this->switchToDatabase('my_database_test_1');
    }

    #[DataProvider('databaseUrls')]
    public function testSwitchToDatabaseWithUrl($testDatabase, $url, $testUrl)
    {
        DB::shouldReceive('purge')->once();

        config()->shouldReceive('get')
            ->once()
            ->with('database.connections.mysql.url', false)
            ->andReturn($url);

        config()->shouldReceive('set')
            ->once()
            ->with('database.connections.mysql.url', $testUrl);

        $this->switchToDatabase($testDatabase);
    }

    public function switchToDatabase($database)
    {
        $instance = new class
        {
            use TestDatabases;
        };

        $method = new ReflectionMethod($instance, 'switchToDatabase');
        $method->invoke($instance, $database);
    }

    public static function databaseUrls()
    {
        return [
            [
                'my_database_test_1',
                'mysql://root:@127.0.0.1/my_database?charset=utf8mb4',
                'mysql://root:@127.0.0.1/my_database_test_1?charset=utf8mb4',
            ],
            [
                'my_database_test_1',
                'mysql://my-user:@localhost/my_database',
                'mysql://my-user:@localhost/my_database_test_1',
            ],
            [
                'my-database_test_1',
                'postgresql://my_database_user:@127.0.0.1/my-database?charset=utf8',
                'postgresql://my_database_user:@127.0.0.1/my-database_test_1?charset=utf8',
            ],
        ];
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        Container::setInstance(null);
        DB::clearResolvedInstances();
        DB::setFacadeApplication(null);

        unset($_SERVER['LARAVEL_PARALLEL_TESTING']);

        m::close();
    }
}
