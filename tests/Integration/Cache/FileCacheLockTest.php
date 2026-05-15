<?php

namespace Illuminate\Tests\Integration\Cache;

use Exception;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Sleep;
use Orchestra\Testbench\Attributes\WithConfig;
use Orchestra\Testbench\TestCase;
use Throwable;

#[WithConfig('cache.default', 'file')]
class FileCacheLockTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // flush lock from previous tests
        Cache::lock('foo')->forceRelease();
    }

    public function testLocksCanBeAcquiredAndReleased()
    {
        $lock = Cache::lock('foo', 10);
        $this->assertTrue($lock->get());
        $this->assertFalse(Cache::lock('foo', 10)->get());
        $lock->release();

        $lock = Cache::lock('foo', 10);
        $this->assertTrue($lock->get());
        $this->assertFalse(Cache::lock('foo', 10)->get());
        Cache::lock('foo')->release();
    }

    public function testLocksCanBlockForSeconds()
    {
        $this->assertSame('taylor', Cache::lock('foo', 10)->block(1, function () {
            return 'taylor';
        }));

        Cache::lock('foo')->forceRelease();
        $this->assertTrue(Cache::lock('foo', 10)->block(1));
    }

    public function testConcurrentLocksAreReleasedSafely()
    {
        Sleep::fake(syncWithCarbon: true);

        $firstLock = Cache::lock('foo', 1);
        $this->assertTrue($firstLock->get());
        Sleep::for(2)->seconds();

        $secondLock = Cache::lock('foo', 10);
        $this->assertTrue($secondLock->get());

        $firstLock->release();

        $this->assertFalse(Cache::lock('foo')->get());
    }

    public function testLocksWithFailedBlockCallbackAreReleased()
    {
        $firstLock = Cache::lock('foo', 10);

        try {
            $firstLock->block(1, function () {
                throw new Exception('failed');
            });
        } catch (Exception) {
            // Not testing the exception, just testing the lock
            // is released regardless of the how the exception
            // thrown by the callback was handled.
        }

        $secondLock = Cache::lock('foo', 1);

        $this->assertTrue($secondLock->get());
    }

    public function testLocksCanBeReleasedUsingOwnerToken()
    {
        $firstLock = Cache::lock('foo', 10);
        $this->assertTrue($firstLock->get());
        $owner = $firstLock->owner();

        $secondLock = Cache::store('file')->restoreLock('foo', $owner);
        $secondLock->release();

        $this->assertTrue(Cache::lock('foo')->get());
    }

    public function testOwnerStatusCanBeCheckedAfterRestoringLock()
    {
        $firstLock = Cache::lock('foo', 10);
        $this->assertTrue($firstLock->get());
        $owner = $firstLock->owner();

        $secondLock = Cache::store('file')->restoreLock('foo', $owner);
        $this->assertTrue($secondLock->isOwnedByCurrentProcess());
    }

    public function testCacheRememberReturnsValueWhenLockWithSameKeyExists()
    {
        $lock = Cache::lock('my-key', 5);
        $this->assertTrue($lock->get());

        $value = Cache::remember('my-key', 60, fn () => 'expected-value');

        $this->assertSame('expected-value', $value);

        $lock->release();
    }

    public function testOtherOwnerDoesNotOwnLockAfterRestore()
    {
        $firstLock = Cache::lock('foo', 10);
        $this->assertTrue($firstLock->isOwnedBy(null));
        $this->assertTrue($firstLock->get());
        $this->assertTrue($firstLock->isOwnedBy($firstLock->owner()));

        $secondLock = Cache::store('file')->restoreLock('foo', 'other_owner');
        $this->assertTrue($secondLock->isOwnedBy($firstLock->owner()));
        $this->assertFalse($secondLock->isOwnedByCurrentProcess());
    }

    public function testExceptionIfBlockCanNotAcquireLock()
    {
        Sleep::fake(syncWithCarbon: true);

        // acquire and not release lock
        Cache::lock('foo', 10)->get();

        // try to get lock and hit block timeout
        $this->expectException(LockTimeoutException::class);
        Cache::lock('foo', 10)->block(5);
    }

    public function testLockCanBeRefreshed()
    {
        $lock = Cache::lock('foo', 10);
        $this->assertTrue($lock->get());

        // Refresh the lock for another 20 seconds
        $this->assertTrue($lock->refresh(20));

        // Lock should still be held
        $this->assertFalse(Cache::lock('foo', 10)->get());

        $lock->release();
    }

    public function testLockCannotBeRefreshedByAnotherOwner()
    {
        $firstLock = Cache::lock('foo', 10);
        $this->assertTrue($firstLock->get());

        // Create a new lock with a different owner
        $secondLock = Cache::store('file')->restoreLock('foo', 'other_owner');

        // Second lock should not be able to refresh
        $this->assertFalse($secondLock->refresh(20));

        // Original lock should still be able to refresh
        $this->assertTrue($firstLock->refresh(20));

        $firstLock->release();
    }

    public function testLockRefreshWithDefaultSeconds()
    {
        $lock = Cache::lock('foo', 10);
        $this->assertTrue($lock->get());

        // Refresh without specifying seconds should use the original duration
        $this->assertTrue($lock->refresh());

        // Lock should still be held
        $this->assertFalse(Cache::lock('foo', 10)->get());

        $lock->release();
    }

    protected function tearDown(): void
    {
        try {
            Cache::lock('foo')->forceRelease();
        } catch (Throwable) {
        }

        parent::tearDown();
    }
}
