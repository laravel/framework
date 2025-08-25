<?php

namespace Illuminate\Tests\Integration\Cache;

use Exception;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Sleep;
use Orchestra\Testbench\Attributes\WithConfig;
use Orchestra\Testbench\TestCase;

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
}
