<?php

namespace Illuminate\Tests\Cache;

use Illuminate\Cache\MemcachedLock;
use Memcached;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;

#[RequiresPhpExtension('memcached')]
class CacheMemcachedLockTest extends TestCase
{
    public function testIsLockedReturnsFalseWhenNotFound()
    {
        $memcached = $this->createStub(Memcached::class);
        $memcached->method('get')->willReturn(false);
        $memcached->method('getResultCode')->willReturn(Memcached::RES_NOTFOUND);
        $lock = new MemcachedLock($memcached, 'foo', 10, 'owner');
        $this->assertFalse($lock->isLocked());
    }

    public function testIsLockedReturnsTrueWhenOwnerIsStored()
    {
        $memcached = $this->createStub(Memcached::class);
        $memcached->method('get')->willReturn('owner');
        $memcached->method('getResultCode')->willReturn(Memcached::RES_SUCCESS);
        $lock = new MemcachedLock($memcached, 'foo', 10, 'owner');
        $this->assertTrue($lock->isLocked());
    }

    public function testIsOwnedByNullWhenNotFound()
    {
        $memcached = $this->createStub(Memcached::class);
        $memcached->method('get')->willReturn(false);
        $memcached->method('getResultCode')->willReturn(Memcached::RES_NOTFOUND);
        $lock = new MemcachedLock($memcached, 'foo', 10, 'owner');
        $this->assertTrue($lock->isOwnedBy(null));
    }

    public function testReleaseReturnsFalseWhenNotFound()
    {
        $memcached = $this->createMock(Memcached::class);
        $memcached->method('get')->willReturn(false);
        $memcached->method('getResultCode')->willReturn(Memcached::RES_NOTFOUND);
        $memcached->expects($this->never())->method('delete');
        $lock = new MemcachedLock($memcached, 'foo', 10, 'owner');
        $this->assertFalse($lock->release());
    }
}
