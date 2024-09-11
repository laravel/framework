<?php

namespace Illuminate\Tests\Integration\Cache;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Orchestra\Testbench\TestCase;

class RepositoryTest extends TestCase
{
    public function testStaleWhileRevalidate(): void
    {
        Carbon::setTestNow('2000-01-01 00:00:00');
        $cache = Cache::driver('array');
        $count = 0;

        // Cache is empty. The value should be populated...
        $value = $cache->flexible('foo', [10, 20], function () use (&$count) {
            return ++$count;
        });

        $this->assertSame(1, $value);
        $this->assertCount(0, defer());
        $this->assertSame(1, $cache->get('foo'));
        $this->assertSame(946684800, $cache->get('foo:created'));

        // Cache is fresh. The value should be retrieved from the cache and used...
        $value = $cache->flexible('foo', [10, 20], function () use (&$count) {
            return ++$count;
        });
        $this->assertSame(1, $value);
        $this->assertCount(0, defer());
        $this->assertSame(1, $cache->get('foo'));
        $this->assertSame(946684800, $cache->get('foo:created'));

        Carbon::setTestNow(now()->addSeconds(11));

        // Cache is now "stale". The stored value should be used and a deferred
        // callback should be registered to refresh the cache.
        $value = $cache->flexible('foo', [10, 20], function () use (&$count) {
            return ++$count;
        });
        $this->assertSame(1, $value);
        $this->assertCount(1, defer());
        $this->assertSame(1, $cache->get('foo'));
        $this->assertSame(946684800, $cache->get('foo:created'));

        // We will hit it again within the same request. This should not queue
        // up an additional deferred callback as only one can be registered at
        // a time for each key.
        $value = $cache->flexible('foo', [10, 20], function () use (&$count) {
            return ++$count;
        });
        $this->assertSame(1, $value);
        $this->assertCount(1, defer());
        $this->assertSame(1, $cache->get('foo'));
        $this->assertSame(946684800, $cache->get('foo:created'));

        // We will now simulate the end of the request lifecycle by executing the
        // deferred callback. This should refresh the cache.
        defer()->invoke();
        $this->assertCount(0, defer());
        $this->assertSame(2, $cache->get('foo')); // this has been updated!
        $this->assertSame(946684811, $cache->get('foo:created')); // this has been updated!

        // Now the cache is fresh again...
        $value = $cache->flexible('foo', [10, 20], function () use (&$count) {
            return ++$count;
        });
        $this->assertSame(2, $value);
        $this->assertCount(0, defer());
        $this->assertSame(2, $cache->get('foo'));
        $this->assertSame(946684811, $cache->get('foo:created'));

        // Let's now progress time beyond the stale TTL...
        Carbon::setTestNow(now()->addSeconds(21));

        // Now the values should have left the cache. We should refresh.
        $value = $cache->flexible('foo', [10, 20], function () use (&$count) {
            return ++$count;
        });
        $this->assertSame(3, $value);
        $this->assertCount(0, defer());
        $this->assertSame(3, $cache->get('foo'));
        $this->assertSame(946684832, $cache->get('foo:created'));

        // Now lets see what happens when another request, job, or command is
        // also trying to refresh the same key at the same time. Will push past
        // the "fresh" TTL and register a deferred callback.
        Carbon::setTestNow(now()->addSeconds(11));
        $value = $cache->flexible('foo', [10, 20], function () use (&$count) {
            return ++$count;
        });
        $this->assertSame(3, $value);
        $this->assertCount(1, defer());
        $this->assertSame(3, $cache->get('foo'));
        $this->assertSame(946684832, $cache->get('foo:created'));

        // Now we will execute the deferred callback but we will first aquire
        // our own lock. This means that the value should not be refreshed by
        // deferred callback.
        /** @var Lock */
        $lock = $cache->lock('illuminate:cache:refresh:lock:foo');

        $this->assertTrue($lock->acquire());
        defer()->first()();
        $this->assertSame(3, $value);
        $this->assertCount(1, defer());
        $this->assertSame(3, $cache->get('foo'));
        $this->assertSame(946684832, $cache->get('foo:created'));
        $this->assertTrue($lock->release());

        // Now we have cleared the lock we will, one last time, confirm that
        // the deferred callack does refresh the value when the lock is not active.
        defer()->invoke();
        $this->assertCount(0, defer());
        $this->assertSame(4, $cache->get('foo'));
        $this->assertSame(946684843, $cache->get('foo:created'));

        // The last thing is to check that we don't refresh the cache in the
        // deferred callback if another thread has already done the work for us.
        // We will make the cache stale...
        Carbon::setTestNow(now()->addSeconds(11));
        $value = $cache->flexible('foo', [10, 20], function () use (&$count) {
            return ++$count;
        });
        $this->assertSame(4, $value);
        $this->assertCount(1, defer());
        $this->assertSame(4, $cache->get('foo'));
        $this->assertSame(946684843, $cache->get('foo:created'));

        // There is now a deferred callback ready to refresh the cache. We will
        // simulate another thread updating the value.
        $cache->putMany([
            'foo' => 99,
            'foo:created' => 946684863,
        ]);

        // then we will run the refresh callback
        defer()->invoke();
        $value = $cache->flexible('foo', [10, 20], function () use (&$count) {
            return ++$count;
        });
        $this->assertSame(99, $value);
        $this->assertCount(0, defer());
        $this->assertSame(99, $cache->get('foo'));
        $this->assertSame(946684863, $cache->get('foo:created'));
    }
}
