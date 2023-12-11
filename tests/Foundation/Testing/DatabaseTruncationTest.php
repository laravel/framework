<?php

namespace Illuminate\Tests\Foundation\Testing;

use Illuminate\Contracts\Console\Kernel as ConsoleKernelContract;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\DatabaseManager;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Foundation\Testing\Concerns\InteractsWithConsole;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Mockery as m;
use Orchestra\Testbench\Concerns\ApplicationTestingHooks;
use Orchestra\Testbench\Foundation\Application as Testbench;
use PHPUnit\Framework\TestCase;

use function Orchestra\Testbench\package_path;

class DatabaseTruncationTest extends TestCase
{
    use ApplicationTestingHooks;
    use DatabaseTruncation;
    use InteractsWithConsole;

    protected function setUp(): void
    {
        RefreshDatabaseState::$migrated = false;

        $this->setUpTheApplicationTestingHooks();
    }

    protected function tearDown(): void
    {
        $this->tearDownTheApplicationTestingHooks();

        RefreshDatabaseState::$migrated = false;
    }

    protected function refreshApplication()
    {
        $this->app = Testbench::create(
            basePath: package_path('vendor/orchestra/testbench-core/laravel'),
        );
    }

    public function testDisconnectionAfterTestCompletion()
    {
        $this->app->instance(ConsoleKernelContract::class, m::spy(ConsoleKernel::class));
        $this->app->instance('db', $database = m::mock(DatabaseManager::class));

        $database->shouldReceive('getConnections')->once()->andReturn([
            'default' => m::mock(ConnectionInterface::class),
            'mysql' => m::mock(ConnectionInterface::class),
        ]);
        $database->shouldReceive('purge')->with('default');
        $database->shouldReceive('purge')->with('mysql');

        $this->truncateDatabaseTables();
    }
}
