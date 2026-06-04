<?php

namespace Illuminate\Tests\Foundation\Testing;

use Illuminate\Contracts\Console\Kernel as ConsoleKernelContract;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Foundation\Testing\Concerns\InteractsWithConsole;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Mockery as m;
use Orchestra\Testbench\Concerns\ApplicationTestingHooks;
use Orchestra\Testbench\Foundation\Application as Testbench;
use PHPUnit\Framework\TestCase;

use function Orchestra\Testbench\package_path;

class LazilyRefreshDatabaseTest extends TestCase
{
    use ApplicationTestingHooks;
    use InteractsWithConsole;
    use LazilyRefreshDatabase;

    public $dropViews = false;

    public $dropTypes = false;

    protected $connectionsToTransact = ['testing', 'testing2'];

    protected function setUp(): void
    {
        RefreshDatabaseState::$migrated = false;

        $this->setUpTheApplicationTestingHooks();
        $this->withoutMockingConsoleOutput();

        $config = $this->app->make('config');
        $config->set(
            'database.connections',
            [
                ...$config->get('database.connections'),
                'testing2' => [
                    'driver' => 'sqlite',
                    'database' => ':memory:',
                ],
            ],
        );
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

    public function testDatabaseIsRefreshedOnInteraction()
    {
        $this->app->instance(ConsoleKernelContract::class, $kernel = m::spy(ConsoleKernel::class));

        $kernel->shouldReceive('call')
            ->once()
            ->with('migrate:fresh', [
                '--drop-views' => false,
                '--drop-types' => false,
                '--seed' => false,
            ]);

        $this->refreshDatabase();
        $this->app->make('db')->select('select 1');
    }

    public function testDatabaseIsNotRefreshedWithoutInteraction()
    {
        $this->app->instance(ConsoleKernelContract::class, $kernel = m::spy(ConsoleKernel::class));

        $kernel->shouldReceive('call')
            ->never();

        $this->refreshDatabase();

        // Some dummy interaction to make sure DB class can be tinkered with
        $this->app->make('db')->getPdo();
    }

    public function testNonDefaultConnectionTriggersRefresh()
    {
        $this->app->instance(ConsoleKernelContract::class, $kernel = m::spy(ConsoleKernel::class));

        $kernel->shouldReceive('call')
            ->once()
            ->with('migrate:fresh', [
                '--drop-views' => false,
                '--drop-types' => false,
                '--seed' => false,
            ]);

        $this->refreshDatabase();

        $this->app->make('db')->connection('testing2')->select('select 1');
    }
}
