<?php

namespace Illuminate\Tests\Integration\Cache;

use Illuminate\Cache\Events\KeyWritten;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Orchestra\Testbench\Attributes\WithMigration;
use Orchestra\Testbench\TestCase;

#[WithMigration('cache')]
class RepositoryTest extends TestCase
{
    use LazilyRefreshDatabase;

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
        $this->assertSame(946684800, $cache->get('illuminate:cache:flexible:created:foo'));

        // Cache is fresh. The value should be retrieved from the cache and used...
        $value = $cache->flexible('foo', [10, 20], function () use (&$count) {
            return ++$count;
        });
        $this->assertSame(1, $value);
        $this->assertCount(0, defer());
        $this->assertSame(1, $cache->get('foo'));
        $this->assertSame(946684800, $cache->get('illuminate:cache:flexible:created:foo'));

        Carbon::setTestNow(now()->addSeconds(11));

        // Cache is now "stale". The stored value should be used and a deferred
        // callback should be registered to refresh the cache.
        $value = $cache->flexible('foo', [10, 20], function () use (&$count) {
            return ++$count;
        });
        $this->assertSame(1, $value);
        $this->assertCount(1, defer());
        $this->assertSame(1, $cache->get('foo'));
        $this->assertSame(946684800, $cache->get('illuminate:cache:flexible:created:foo'));

        // We will hit it again within the same request. This should not queue
        // up an additional deferred callback as only one can be registered at
        // a time for each key.
        $value = $cache->flexible('foo', [10, 20], function () use (&$count) {
            return ++$count;
        });
        $this->assertSame(1, $value);
        $this->assertCount(1, defer());
        $this->assertSame(1, $cache->get('foo'));
        $this->assertSame(946684800, $cache->get('illuminate:cache:flexible:created:foo'));

        // We will now simulate the end of the request lifecycle by executing the
        // deferred callback. This should refresh the cache.
        defer()->invoke();
        $this->assertCount(0, defer());
        $this->assertSame(2, $cache->get('foo')); // this has been updated!
        $this->assertSame(946684811, $cache->get('illuminate:cache:flexible:created:foo')); // this has been updated!

        // Now the cache is fresh again...
        $value = $cache->flexible('foo', [10, 20], function () use (&$count) {
            return ++$count;
        });
        $this->assertSame(2, $value);
        $this->assertCount(0, defer());
        $this->assertSame(2, $cache->get('foo'));
        $this->assertSame(946684811, $cache->get('illuminate:cache:flexible:created:foo'));

        // Let's now progress time beyond the stale TTL...
        Carbon::setTestNow(now()->addSeconds(21));

        // Now the values should have left the cache. We should refresh.
        $value = $cache->flexible('foo', [10, 20], function () use (&$count) {
            return ++$count;
        });
        $this->assertSame(3, $value);
        $this->assertCount(0, defer());
        $this->assertSame(3, $cache->get('foo'));
        $this->assertSame(946684832, $cache->get('illuminate:cache:flexible:created:foo'));

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
        $this->assertSame(946684832, $cache->get('illuminate:cache:flexible:created:foo'));

        // Now we will execute the deferred callback but we will first aquire
        // our own lock. This means that the value should not be refreshed by
        // deferred callback.
        /** @var Lock */
        $lock = $cache->lock('illuminate:cache:flexible:lock:foo');

        $this->assertTrue($lock->acquire());
        defer()->first()();
        $this->assertSame(3, $value);
        $this->assertCount(1, defer());
        $this->assertSame(3, $cache->get('foo'));
        $this->assertSame(946684832, $cache->get('illuminate:cache:flexible:created:foo'));
        $this->assertTrue($lock->release());

        // Now we have cleared the lock we will, one last time, confirm that
        // the deferred callack does refresh the value when the lock is not active.
        defer()->invoke();
        $this->assertCount(0, defer());
        $this->assertSame(4, $cache->get('foo'));
        $this->assertSame(946684843, $cache->get('illuminate:cache:flexible:created:foo'));

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
        $this->assertSame(946684843, $cache->get('illuminate:cache:flexible:created:foo'));

        // There is now a deferred callback ready to refresh the cache. We will
        // simulate another thread updating the value.
        $cache->putMany([
            'foo' => 99,
            'illuminate:cache:flexible:created:foo' => 946684863,
        ]);

        // then we will run the refresh callback
        defer()->invoke();
        $value = $cache->flexible('foo', [10, 20], function () use (&$count) {
            return ++$count;
        });
        $this->assertSame(99, $value);
        $this->assertCount(0, defer());
        $this->assertSame(99, $cache->get('foo'));
        $this->assertSame(946684863, $cache->get('illuminate:cache:flexible:created:foo'));
    }

    public function testItHandlesStrayTtlKeyAfterMainKeyIsForgotten()
    {
        $cache = Cache::driver('array');
        $count = 0;

        $value = $cache->flexible('count', [5, 10], function () use (&$count) {
            $count = 1;

            return $count;
        });

        $this->assertSame(1, $value);
        $this->assertSame(1, $count);

        $cache->forget('count');

        $value = $cache->flexible('count', [5, 10], function () use (&$count) {
            $count = 2;

            return $count;
        });
        $this->assertSame(2, $value);
        $this->assertSame(2, $count);
    }

    public function testItImplicitlyClearsTtlKeysFromDatabaseCache()
    {
        $this->freezeTime();
        $cache = Cache::driver('database');

        $cache->flexible('count', [5, 10], fn () => 1);

        $this->assertTrue($cache->has('count'));
        $this->assertTrue($cache->has('illuminate:cache:flexible:created:count'));

        $cache->forget('count');

        $this->assertEmpty($cache->getConnection()->table('cache')->get());
        $this->assertTrue($cache->missing('count'));
        $this->assertTrue($cache->missing('illuminate:cache:flexible:created:count'));

        $cache->flexible('count', [5, 10], fn () => 1);

        $this->assertTrue($cache->has('count'));
        $this->assertTrue($cache->has('illuminate:cache:flexible:created:count'));

        $this->travel(20)->seconds();
        $cache->forgetIfExpired('count');

        $this->assertEmpty($cache->getConnection()->table('cache')->get());
        $this->assertTrue($cache->missing('count'));
        $this->assertTrue($cache->missing('illuminate:cache:flexible:created:count'));
    }

    public function testItImplicitlyClearsTtlKeysFromFileDriver()
    {
        $this->freezeTime();
        $cache = Cache::driver('file');

        $cache->flexible('count', [5, 10], fn () => 1);

        $this->assertTrue($cache->has('count'));
        $this->assertTrue($cache->has('illuminate:cache:flexible:created:count'));

        $cache->forget('count');

        $this->assertFalse($cache->getFilesystem()->exists($cache->path('count')));
        $this->assertFalse($cache->getFilesystem()->exists($cache->path('illuminate:cache:flexible:created:count')));
        $this->assertTrue($cache->missing('count'));
        $this->assertTrue($cache->missing('illuminate:cache:flexible:created:count'));

        $cache->flexible('count', [5, 10], fn () => 1);

        $this->assertTrue($cache->has('count'));
        $this->assertTrue($cache->has('illuminate:cache:flexible:created:count'));

        $this->travel(20)->seconds();

        $this->assertTrue($cache->missing('count'));
        $this->assertFalse($cache->getFilesystem()->exists($cache->path('count')));
        $this->assertFalse($cache->getFilesystem()->exists($cache->path('illuminate:cache:flexible:created:count')));
        $this->assertTrue($cache->missing('illuminate:cache:flexible:created:count'));
    }

    public function testItRoundsDateTimeValuesToAccountForTimePassedDuringScriptExecution()
    {
        // do not freeze time as this test depends on time progressing duration execution.
        $cache = Cache::driver('array');
        $events = [];
        Event::listen(function (KeyWritten $event) use (&$events) {
            $events[] = $event;
        });

        $result = $cache->put('foo', 'bar', now()->addSecond());

        $this->assertTrue($result);
        $this->assertCount(1, $events);
        $this->assertSame('foo', $events[0]->key);
        $this->assertSame(1, $events[0]->seconds);
    }
}
