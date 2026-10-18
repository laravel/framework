<?php

namespace Illuminate\Tests\Foundation\Testing\Concerns;

use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Foundation\Testing\TestCase as FoundationTestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Orchestra\Testbench\Concerns\CreatesApplication;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

use function Orchestra\Testbench\default_migration_path;

class InteractsWithTestCaseLifecycleTest extends TestCase
{
    protected function tearDown(): void
    {
        RefreshDatabaseState::$inMemoryConnections = [];
        RefreshDatabaseState::$migrated = false;

        parent::tearDown();
    }

    public function testTearDownReleasesUniqueJobLocksTakenWhileTheQueueWasFaked()
    {
        $testCase = new TestCaseWithFakedQueue('foo');

        (new ReflectionMethod($testCase, 'setUpTheTestEnvironment'))->invoke($testCase);

        $store = $testCase->dispatchUniqueJob();

        $this->assertArrayHasKey('laravel_unique_job:'.LifecycleUniqueJob::class.':', $store->locks);

        (new ReflectionMethod($testCase, 'tearDownTheTestEnvironment'))->invoke($testCase);

        $this->assertSame([], $store->locks);
    }

    public function testTearDownIgnoresLockReleaseFailuresWithDatabaseCacheOnInMemorySqliteAndRefreshDatabase()
    {
        // sqlite :memory: + "database" cache store + RefreshDatabase: the transaction is rolled back and the
        // connection disconnected before the locks are released, so the release hits a new in-memory database
        // without a "cache_locks" table and throws. Other RefreshDatabase setups release normally.
        $testCase = new DatabaseCacheTestCaseWithFakedQueue('foo');

        (new ReflectionMethod($testCase, 'setUpTheTestEnvironment'))->invoke($testCase);

        $this->assertSame(1, $testCase->dispatchUniqueJobAndCountLocks());

        (new ReflectionMethod($testCase, 'tearDownTheTestEnvironment'))->invoke($testCase);

        $this->assertFalse($testCase->hasApplication());
    }
}

class TestCaseWithFakedQueue extends FoundationTestCase
{
    use CreatesApplication;

    public function dispatchUniqueJob()
    {
        Queue::fake();

        dispatch(new LifecycleUniqueJob);

        return $this->app['cache']->store()->getStore();
    }
}

class DatabaseCacheTestCaseWithFakedQueue extends FoundationTestCase
{
    use CreatesApplication, RefreshDatabase;

    protected function defineEnvironment($app)
    {
        $app['config']->set('database.default', 'lifecycle');
        $app['config']->set('database.connections.lifecycle', ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
        $app['config']->set('cache.default', 'database');
        $app['config']->set('cache.stores.database.connection', 'lifecycle');
    }

    protected function migrateFreshUsing()
    {
        return ['--path' => default_migration_path(), '--realpath' => true];
    }

    public function dispatchUniqueJobAndCountLocks()
    {
        Queue::fake();

        dispatch(new LifecycleUniqueJob);

        return DB::table('cache_locks')->count();
    }

    public function hasApplication()
    {
        return $this->app !== null;
    }
}

class LifecycleUniqueJob implements ShouldQueue, ShouldBeUnique
{
    //
}
