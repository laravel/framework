<?php

namespace Illuminate\Tests\Cache;

use Illuminate\Cache\FileStore;
use Illuminate\Cache\FileLock;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\TestCase;

class CacheFileLockTest extends TestCase
{
    public function testLockCanBeAcquired()
    {
        $name = uniqid();
        $owner = 'owner';
        $seconds = 2;
        $directory = sys_get_temp_dir();
        $filesystem = new Filesystem();
        $store = new FileStore($filesystem, $directory);
        $lock = new FileLock($store, $name, $seconds, $owner);

        // Lock can be acquired since it does not exist
        $this->assertTrue($lock->acquire());

        // Simulate long process
        sleep($seconds);

        // Lock cannot be acquired before it is released
        $this->assertFalse($lock->acquire());

        // Release the lock
        $lock->release();

        // Lock should be should be available after a release
        $this->assertTrue($lock->acquire());
    }
}
