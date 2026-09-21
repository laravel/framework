<?php

namespace Illuminate\Tests\Foundation\Testing;

use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Orchestra\Testbench\Concerns\ApplicationTestingHooks;
use Orchestra\Testbench\Foundation\Application as Testbench;
use PHPUnit\Framework\TestCase;

use function Orchestra\Testbench\package_path;

class RefreshDatabaseWithTruncationTest extends TestCase
{
    use ApplicationTestingHooks;
    use DatabaseTruncation;
    use RefreshDatabase;

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

    public function testCommittedTransactionDoesNotInvalidateTheMigratedState()
    {
        RefreshDatabaseState::$migrated = true;

        $this->beginDatabaseTransaction();

        $this->app->make('db')->connection()->commit();

        $this->callBeforeApplicationDestroyedCallbacks();

        $this->assertTrue(RefreshDatabaseState::$migrated);
    }
}
