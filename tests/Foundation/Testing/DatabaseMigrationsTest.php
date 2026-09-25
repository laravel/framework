<?php

namespace Illuminate\Tests\Foundation\Testing;

use Illuminate\Contracts\Console\Kernel as ConsoleKernelContract;
use Illuminate\Foundation\Testing\Concerns\InteractsWithConsole;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Orchestra\Testbench\Concerns\ApplicationTestingHooks;
use Orchestra\Testbench\Foundation\Application as Testbench;
use PHPUnit\Framework\TestCase;

use function Orchestra\Testbench\package_path;

class DatabaseMigrationsTest extends TestCase
{
    use ApplicationTestingHooks;
    use DatabaseMigrations;
    use InteractsWithConsole;

    public $dropViews = false;

    public $dropTypes = false;

    protected function setUp(): void
    {
        RefreshDatabaseState::$migrated = false;

        $this->afterApplicationCreated(function () {
            $this->app['config']->set([
                'database.default' => 'testing',
                'database.connections.testing' => [
                    'driver' => 'sqlite',
                    'database' => ':memory:',
                ],
            ]);
        });

        $this->setUpTheApplicationTestingHooks();
        $this->withoutMockingConsoleOutput();
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

    public function testRefreshTestDatabaseDefault()
    {
        $kernel = new RecordingConsoleKernel;
        $this->app->instance(ConsoleKernelContract::class, $kernel);

        // beforeApplicationDestroyed() callbacks run most-recently-registered
        // first, so registering ours before runDatabaseMigrations() ensures
        // its own migrate:rollback callback has already fired by the time
        // this one checks the full call list.
        $this->beforeApplicationDestroyed(function () use ($kernel) {
            $this->assertSame([
                ['migrate:fresh', ['--drop-views' => false, '--drop-types' => false, '--seed' => false]],
                ['migrate:rollback', []],
            ], $kernel->calls);
        });

        $this->runDatabaseMigrations();

        $this->assertSame([
            ['migrate:fresh', ['--drop-views' => false, '--drop-types' => false, '--seed' => false]],
        ], $kernel->calls);
    }

    public function testRefreshTestDatabaseWithDropViewsOption()
    {
        $this->dropViews = true;

        $kernel = new RecordingConsoleKernel;
        $this->app->instance(ConsoleKernelContract::class, $kernel);

        $this->beforeApplicationDestroyed(function () use ($kernel) {
            $this->assertSame([
                ['migrate:fresh', ['--drop-views' => true, '--drop-types' => false, '--seed' => false]],
                ['migrate:rollback', []],
            ], $kernel->calls);
        });

        $this->runDatabaseMigrations();

        $this->assertSame([
            ['migrate:fresh', ['--drop-views' => true, '--drop-types' => false, '--seed' => false]],
        ], $kernel->calls);
    }

    public function testRefreshTestDatabaseWithDropTypesOption()
    {
        $this->dropTypes = true;

        $kernel = new RecordingConsoleKernel;
        $this->app->instance(ConsoleKernelContract::class, $kernel);

        $this->beforeApplicationDestroyed(function () use ($kernel) {
            $this->assertSame([
                ['migrate:fresh', ['--drop-views' => false, '--drop-types' => true, '--seed' => false]],
                ['migrate:rollback', []],
            ], $kernel->calls);
        });

        $this->runDatabaseMigrations();

        $this->assertSame([
            ['migrate:fresh', ['--drop-views' => false, '--drop-types' => true, '--seed' => false]],
        ], $kernel->calls);
    }
}
