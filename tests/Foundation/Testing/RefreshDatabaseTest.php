<?php

namespace Illuminate\Tests\Foundation\Testing;

use Illuminate\Foundation\Testing\Concerns\InteractsWithConsole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Mockery as m;
use Orchestra\Testbench\Concerns\Testing;
use Orchestra\Testbench\Foundation\Application as Testbench;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use RuntimeException;

use function Orchestra\Testbench\package_path;

class RefreshDatabaseTest extends TestCase
{
    protected $traitObject;

    protected function setUp(): void
    {
        RefreshDatabaseState::$migrated = false;

        $this->traitObject = m::mock(RefreshDatabaseTestMockClass::class)->makePartial();
        $this->traitObject->setUp();
    }

    protected function tearDown(): void
    {
        $this->traitObject->tearDown();

        if ($container = m::getContainer()) {
            $this->addToAssertionCount($container->mockery_getExpectationCount());
        }

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
            ]);

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
            ]);

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
            ]);

        $refreshTestDatabaseReflection = $this->__reflectAndSetupAccessibleForProtectedTraitMethod('refreshTestDatabase');

        $refreshTestDatabaseReflection->invoke($this->traitObject);
    }

    public function testSuccessWithCheckTransactionLevel()
    {
        $refreshTestDatabaseReflection = $this->__reflectAndSetupAccessibleForProtectedTraitMethod('refreshTestDatabase');

        $refreshTestDatabaseReflection->invoke($this->traitObject);

        $this->traitObject->withCheckTransactionLevel();

        $this->traitObject->callBeforeApplicationDestroyedCallbacks();
        $this->assertNull($this->traitObject->__getCallbackException());
    }

    public function testErrorOnExcessiveTransactionCommit()
    {
        $refreshTestDatabaseReflection = $this->__reflectAndSetupAccessibleForProtectedTraitMethod('refreshTestDatabase');

        $refreshTestDatabaseReflection->invoke($this->traitObject);

        $this->traitObject->withCheckTransactionLevel();
        $connection = $this->traitObject->__getConnection();
        $connection->commit();

        $this->traitObject->callBeforeApplicationDestroyedCallbacks();
        $this->assertInstanceOf(RuntimeException::class, $this->traitObject->__getCallbackException());
    }

    public function testNoErrorThrownWithoutCheckTransactionLevel()
    {
        $refreshTestDatabaseReflection = $this->__reflectAndSetupAccessibleForProtectedTraitMethod('refreshTestDatabase');

        $refreshTestDatabaseReflection->invoke($this->traitObject);

        // This is the default, so there is no need to actually specify it.
        $this->traitObject->withoutCheckTransactionLevel();

        $connection = $this->traitObject->__getConnection();
        $connection->beginTransaction();

        $this->traitObject->callBeforeApplicationDestroyedCallbacks();
        $this->assertNull($this->traitObject->__getCallbackException());
    }
}

class RefreshDatabaseTestMockClass
{
    use InteractsWithConsole;
    use RefreshDatabase;
    use Testing;

    public $dropViews = false;

    public $dropTypes = false;

    public function setUp()
    {
        RefreshDatabaseState::$migrated = false;

        $this->app = $this->refreshApplication();
        $this->withoutMockingConsoleOutput();
    }

    public function tearDown()
    {
        RefreshDatabaseState::$migrated = false;

        $this->callBeforeApplicationDestroyedCallbacks();
        $this->app?->flush();
    }

    protected function setUpTraits()
    {
        return [];
    }

    protected function setUpTheTestEnvironmentTraitToBeIgnored(string $use): bool
    {
        return true;
    }

    public function refreshApplication()
    {
        return Testbench::create(
            basePath: package_path('vendor/orchestra/testbench-core/laravel')
        );
    }

    public function __getConnection()
    {
        return $this->app->get('db.connection');
    }

    public function __getCallbackException()
    {
        return $this->callbackException;
    }
}
