<?php

namespace Illuminate\Tests\Foundation\Testing;

use Illuminate\Foundation\Testing\Concerns\InteractsWithConsole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Mockery as m;
use Orchestra\Testbench\Foundation\Application as Testbench;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

use function Orchestra\Testbench\package_path;

class RefreshDatabaseTest extends TestCase
{
    protected $traitObject;

    protected function setUp(): void
    {
        RefreshDatabaseState::$migrated = false;

        $this->traitObject = m::mock(RefreshDatabaseTestMockClass::class)->makePartial();

        $app = Testbench::create(
            basePath: package_path('vendor/orchestra/testbench-core/laravel')
        );

        $this->traitObject->app = $app;
    }

    protected function tearDown(): void
    {
        m::close();
    }

    private function __reflectAndSetupAccessibleForProtectedTraitMethod($methodName)
    {
        $migrateFreshUsingReflection = new ReflectionMethod(
            get_class($this->traitObject),
            $methodName
        );

        return $migrateFreshUsingReflection;
    }

    public function testRefreshTestDatabaseDefault()
    {
        $this->traitObject
            ->shouldReceive('artisan')
            ->once()
            ->with('migrate:fresh', [
                '--drop-views' => false,
                '--drop-types' => false,
                '--seed' => false,
            ])->andReturnUsing(function () {
                $this->addToAssertionCount(1);
            });

        $refreshTestDatabaseReflection = $this->__reflectAndSetupAccessibleForProtectedTraitMethod('refreshTestDatabase');

        $refreshTestDatabaseReflection->invoke($this->traitObject);
    }

    public function testRefreshTestDatabaseWithDropViewsOption()
    {
        $this->traitObject->dropViews = true;

        $this->traitObject
            ->shouldReceive('artisan')
            ->once()
            ->with('migrate:fresh', [
                '--drop-views' => true,
                '--drop-types' => false,
                '--seed' => false,
            ])->andReturnUsing(function () {
                $this->addToAssertionCount(1);
            });

        $refreshTestDatabaseReflection = $this->__reflectAndSetupAccessibleForProtectedTraitMethod('refreshTestDatabase');

        $refreshTestDatabaseReflection->invoke($this->traitObject);
    }

    public function testRefreshTestDatabaseWithDropTypesOption()
    {
        $this->traitObject->dropTypes = true;

        $this->traitObject
            ->shouldReceive('artisan')
            ->once()
            ->with('migrate:fresh', [
                '--drop-views' => false,
                '--drop-types' => true,
                '--seed' => false,
            ])->andReturnUsing(function () {
                $this->addToAssertionCount(1);
            });

        $refreshTestDatabaseReflection = $this->__reflectAndSetupAccessibleForProtectedTraitMethod('refreshTestDatabase');

        $refreshTestDatabaseReflection->invoke($this->traitObject);
    }
}

class RefreshDatabaseTestMockClass
{
    use InteractsWithConsole;
    use RefreshDatabase;

    public $app;

    public $dropViews = false;

    public $dropTypes = false;

    public function __construct()
    {
        $this->withoutMockingConsoleOutput();
    }

    public function beforeApplicationDestroyed()
    {
        //
    }
}
