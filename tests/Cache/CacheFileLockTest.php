<?php

namespace Illuminate\Tests\Cache;

use Illuminate\Cache\FileLock;
use Illuminate\Cache\FileStore;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class CacheFileLockTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->directory = sys_get_temp_dir();
        $this->filesystem = new Filesystem();
    }

    public function testLockCanBeAcquired()
    {
        $owner = 'owner';
        $seconds = 2;
        $store = new FileStore($this->filesystem, $this->directory);
        $lock = new FileLock($store, uniqid(), $seconds, $owner);

        // Lock can be acquired since it does not exist
        $this->assertTrue($lock->acquire());

        // Simulate long process
        sleep($seconds);

        // Lock cannot be acquired before it is released
        $this->assertFalse($lock->acquire());

        // Release the lock
        $lock->release();

        // Lock should be available after a release
        $this->assertTrue($lock->acquire());

        // Release the lock at the end of the test
        $lock->release();
    }

    public function testCannotAcquireLockTwice()
    {
        $store = new FileStore($this->filesystem, $this->directory);
        $lock = $store->lock(uniqid(), 10);

        $this->assertTrue($lock->acquire());
        $this->assertFalse($lock->acquire());
    }

    public function testCanAcquireLockAgainAfterExpiry()
    {
        Carbon::setTestNow(Carbon::now());
        $store = new FileStore($this->filesystem, $this->directory);
        $lock = $store->lock(uniqid(), 10);
        $lock->acquire();
        Carbon::setTestNow(Carbon::now()->addSeconds(10));

        $this->assertTrue($lock->acquire());

        $lock->release();
    }

    public function testLockExpirationLowerBoundary()
    {
        Carbon::setTestNow(Carbon::now());
        $store = new FileStore($this->filesystem, $this->directory);
        $lock = $store->lock(uniqid(), 10);
        $lock->acquire();
        Carbon::setTestNow(Carbon::now()->addSeconds(10)->subMicrosecond());

        $this->assertFalse($lock->acquire());

        $lock->release();
    }

    public function testLockWithNoExpirationNeverExpires()
    {
        Carbon::setTestNow(Carbon::now());
        $store = new FileStore($this->filesystem, $this->directory);
        $lock = $store->lock(uniqid());
        $lock->acquire();
        Carbon::setTestNow(Carbon::now()->addYears(100));

        $this->assertFalse($lock->acquire());

        $lock->release();
    }

    public function testCanAcquireLockAfterRelease()
    {
        $store = new FileStore($this->filesystem, $this->directory);
        $lock = $store->lock(uniqid(), 10);
        $lock->acquire();

        $this->assertTrue($lock->release());
        $this->assertTrue($lock->acquire());

        $lock->release();
    }

    public function testAnotherOwnerCannotReleaseLock()
    {
        $name = uniqid();
        $store = new FileStore($this->filesystem, $this->directory);
        $owner = $store->lock($name, 10);
        $wannabeOwner = $store->lock($name, 10);
        $owner->acquire();

        $this->assertFalse($wannabeOwner->release());
    }

    public function testAnotherOwnerCanForceReleaseALock()
    {
        $name = uniqid();
        $store = new FileStore($this->filesystem, $this->directory);
        $owner = $store->lock($name, 10);
        $wannabeOwner = $store->lock($name, 10);
        $owner->acquire();
        $wannabeOwner->forceRelease();

        $this->assertTrue($wannabeOwner->acquire());
    }
}
