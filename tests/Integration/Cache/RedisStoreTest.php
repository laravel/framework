<?php

namespace Illuminate\Tests\Integration\Cache;

use DateTime;
use Illuminate\Cache\RedisStore;
use Illuminate\Foundation\Testing\Concerns\InteractsWithRedis;
use Illuminate\Redis\Connections\PhpRedisClusterConnection;
use Illuminate\Redis\Connections\PredisClusterConnection;
use Illuminate\Redis\Events\CommandExecuted;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Sleep;
use Illuminate\Support\Str;
use Mockery;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\Attributes\TestWith;
use RuntimeException;

#[RequiresPhpExtension('redis')]
class RedisStoreTest extends TestCase
{
    use InteractsWithRedis;

    protected function setUp(): void
    {
        $this->afterApplicationCreated(function () {
            $this->setUpRedis();
        });

        $this->beforeApplicationDestroyed(function () {
            $this->tearDownRedis();
        });

        parent::setUp();
    }

    public function testCacheTtl(): void
    {
        $store = Cache::store('redis');
        $store->clear();

        while ((microtime(true) - time()) > 0.5 && (microtime(true) - time()) < 0.6) {
            //
        }

        $store->put('hello', 'world', 1);
        $putAt = microtime(true);

        Sleep::for(600)->milliseconds();
        $this->assertTrue((microtime(true) - $putAt) < 1);
        $this->assertSame('world', $store->get('hello'));

        // Although this key expires after exactly 1 second, Redis has a
        // 0-1 millisecond error rate on expiring keys (as of Redis 2.6) so
        // for a non-flakey test we need to account for the millisecond.
        // see: https://redis.io/commands/expire/
        while ((microtime(true) - $putAt) < 1.001) {
            //
        }

        $this->assertNull($store->get('hello'));
    }

    public function testItCanStoreInfinite()
    {
        Cache::store('redis')->clear();

        $result = Cache::store('redis')->put('foo', INF);
        $this->assertTrue($result);
        $this->assertSame(INF, Cache::store('redis')->get('foo'));

        $result = Cache::store('redis')->put('bar', -INF);
        $this->assertTrue($result);
        $this->assertSame(-INF, Cache::store('redis')->get('bar'));
    }

    public function testItCanStoreNan()
    {
        Cache::store('redis')->clear();

        $result = Cache::store('redis')->put('foo', NAN);
        $this->assertTrue($result);
        $this->assertNan(Cache::store('redis')->get('foo'));
    }

    public function testItCanExpireWithZeroTTL()
    {
        Cache::store('redis')->clear();

        $result = Cache::store('redis')->put('foo', 10, 10);
        $this->assertTrue($result);

        $result = Cache::store('redis')->put('foo', 10, 0);
        $this->assertTrue($result);

        $value = Cache::store('redis')->get('foo');
        $this->assertNull($value);
    }

    #[TestWith(['laravel_cache_'])]
    #[TestWith(['laravel-cache-'])]
    public function testTagsCanBeAccessed(string $cachePrefix)
    {
        config(['cache.prefix' => $cachePrefix]);

        Cache::store('redis')->clear();

        Cache::store('redis')->tags(['people', 'author'])->put('name', 'Sally', 5);
        Cache::store('redis')->tags(['people', 'author'])->put('age', 30, 5);

        $this->assertSame('Sally', Cache::store('redis')->tags(['people', 'author'])->get('name'));
        $this->assertEquals(30, Cache::store('redis')->tags(['people', 'author'])->get('age'));

        Cache::store('redis')->tags(['people', 'author'])->flush();

        $keyCount = Cache::store('redis')->connection()->keys('*');
        $this->assertCount(0, $keyCount);
    }

    public function testTagEntriesCanBeStoredForever()
    {
        Cache::store('redis')->clear();

        Cache::store('redis')->tags(['people', 'author'])->forever('name', 'Sally');
        Cache::store('redis')->tags(['people', 'author'])->forever('age', 30);

        $this->assertSame('Sally', Cache::store('redis')->tags(['people', 'author'])->get('name'));
        $this->assertEquals(30, Cache::store('redis')->tags(['people', 'author'])->get('age'));

        Cache::store('redis')->tags(['people', 'author'])->flush();

        $keyCount = Cache::store('redis')->connection()->keys('*');
        $this->assertCount(0, $keyCount);
    }

    public function testTagEntriesCanBeIncremented()
    {
        Cache::store('redis')->clear();

        Cache::store('redis')->tags(['votes'])->put('person-1', 0, 5);
        Cache::store('redis')->tags(['votes'])->increment('person-1');
        Cache::store('redis')->tags(['votes'])->increment('person-1');

        $this->assertEquals(2, Cache::store('redis')->tags(['votes'])->get('person-1'));

        Cache::store('redis')->tags(['votes'])->decrement('person-1');
        Cache::store('redis')->tags(['votes'])->decrement('person-1');

        $this->assertEquals(0, Cache::store('redis')->tags(['votes'])->get('person-1'));
    }

    public function testTagEntriesCanBeDecrementedUsingEnumKeys()
    {
        Cache::store('redis')->clear();

        Cache::store('redis')->tags(['votes'])->put(RedisTaggedCacheTestKey::PERSON_1, 2, 5);
        Cache::store('redis')->tags(['votes'])->decrement(RedisTaggedCacheTestKey::PERSON_1);

        $this->assertEquals(1, Cache::store('redis')->tags(['votes'])->get(RedisTaggedCacheTestKey::PERSON_1));

        Cache::store('redis')->tags(['votes'])->flush();

        $this->assertNull(Cache::store('redis')->tags(['votes'])->get(RedisTaggedCacheTestKey::PERSON_1));
    }

    public function testIncrementedTagEntriesProperlyTurnStale()
    {
        Cache::store('redis')->clear();

        Cache::store('redis')->tags(['votes'])->add('person-1', 0, $seconds = 1);
        Cache::store('redis')->tags(['votes'])->increment('person-1');
        Cache::store('redis')->tags(['votes'])->increment('person-1');

        sleep(2);

        Cache::store('redis')->tags(['votes'])->flushStale();

        $keyCount = Cache::store('redis')->connection()->keys('*');
        $this->assertCount(0, $keyCount);
    }

    public function testPastTtlTagEntriesAreNotAdded()
    {
        Cache::store('redis')->clear();

        Cache::store('redis')->tags(['votes'])->add('person-1', 0, new DateTime('yesterday'));

        $value = Cache::store('redis')->tags(['votes'])->get('person-1');
        $this->assertNull($value);

        $keyCount = Cache::store('redis')->connection()->keys('*');
        $this->assertCount(0, $keyCount);
    }

    public function testPutPastTtlTagEntriesProperlyTurnStale()
    {
        Cache::store('redis')->clear();

        Cache::store('redis')->tags(['votes'])->put('person-1', 0, new DateTime('yesterday'));
        Cache::store('redis')->tags(['votes'])->flushStale();

        $keyCount = Cache::store('redis')->connection()->keys('*');
        $this->assertCount(0, $keyCount);
    }

    public function testTagsCanBeFlushedBySingleKey()
    {
        Cache::store('redis')->clear();

        Cache::store('redis')->tags(['people', 'author'])->put('person-1', 'Sally', 5);
        Cache::store('redis')->tags(['people', 'artist'])->put('person-2', 'John', 5);

        Cache::store('redis')->tags(['artist'])->flush();

        $this->assertSame('Sally', Cache::store('redis')->tags(['people', 'author'])->get('person-1'));
        $this->assertNull(Cache::store('redis')->tags(['people', 'artist'])->get('person-2'));

        $keyCount = Cache::store('redis')->connection()->keys('*');
        $this->assertCount(3, $keyCount); // Sets for people, authors, and actual entry for Sally
    }

    public function testStaleEntriesCanBeFlushed()
    {
        Cache::store('redis')->clear();

        Cache::store('redis')->tags(['people', 'author'])->put('person-1', 'Sally', 1);
        Cache::store('redis')->tags(['people', 'artist'])->put('person-2', 'John', 1);

        sleep(2);

        // Add a non-stale entry to people...
        Cache::store('redis')->tags(['people', 'author'])->put('person-3', 'Jennifer', 5);

        Cache::store('redis')->tags(['people'])->flushStale();

        $keyCount = Cache::store('redis')->connection()->keys('*');
        $this->assertCount(4, $keyCount); // Sets for people, authors, and artists + individual entry for Jennifer
    }

    public function testTouchedTagEntriesAreNotConsideredStale()
    {
        Cache::store('redis')->clear();

        Cache::store('redis')->tags(['people'])->put('person-1', 'Sally', 1);
        Cache::store('redis')->tags(['people'])->touch('person-1', 5);

        sleep(2);

        Cache::store('redis')->tags(['people'])->flushStale();

        $this->assertSame('Sally', Cache::store('redis')->tags(['people'])->get('person-1'));

        Cache::store('redis')->tags(['people'])->flush();

        $this->assertNull(Cache::store('redis')->tags(['people'])->get('person-1'));

        $keyCount = Cache::store('redis')->connection()->keys('*');
        $this->assertCount(0, $keyCount);
    }

    public function testMultipleItemsCanBeSetAndRetrieved()
    {
        $store = Cache::store('redis');
        $result = $store->put('foo', 'bar', 10);
        $resultMany = $store->putMany([
            'fizz' => 'buz',
            'quz' => 'baz',
        ], 10);
        $this->assertTrue($result);
        $this->assertTrue($resultMany);
        $this->assertEquals([
            'foo' => 'bar',
            'fizz' => 'buz',
            'quz' => 'baz',
            'norf' => null,
        ], $store->many(['foo', 'fizz', 'quz', 'norf']));

        $this->assertSame([], $store->many([]));
    }

    public function testPutManyCallsPutWhenClustered()
    {
        $store = Mockery::mock(RedisStore::class)->makePartial();
        $store->expects('connection')->andReturn(Mockery::mock(PhpRedisClusterConnection::class));
        $store->expects('put')
            ->twice()
            ->andReturn(true);

        $store->putMany([
            'foo' => 'bar',
            'fizz' => 'buz',
        ], 10);
    }

    public function testIncrementWithSerializationEnabled()
    {
        $this->markTestSkipped('Test makes no sense anymore. Application must explicitly wrap such code in runClean() when used with serialization/compression enabled.');

        /** @var \Illuminate\Cache\RedisStore $store */
        $store = Cache::store('redis');
        /** @var \Redis $client */
        $client = $store->connection()->client();
        $client->setOption(\Redis::OPT_SERIALIZER, \Redis::SERIALIZER_PHP);

        $store->flush();
        $store->add('foo', 1, 10);
        $this->assertEquals(1, $store->get('foo'));

        $store->increment('foo');
        $this->assertEquals(2, $store->get('foo'));
    }

    public function testTagsCanBeFlushedWithLargeNumberOfKeys()
    {
        Cache::store('redis')->clear();

        $tags = ['large-test-'.time()];

        for ($i = 1; $i <= 5000; $i++) {
            Cache::store('redis')->tags($tags)->put("key:{$i}", "value:{$i}", 300);
        }

        $this->assertSame('value:1', Cache::store('redis')->tags($tags)->get('key:1'));
        $this->assertSame('value:2500', Cache::store('redis')->tags($tags)->get('key:2500'));
        $this->assertSame('value:5000', Cache::store('redis')->tags($tags)->get('key:5000'));

        Cache::store('redis')->tags($tags)->flush();

        $this->assertNull(Cache::store('redis')->tags($tags)->get('key:1'));
        $this->assertNull(Cache::store('redis')->tags($tags)->get('key:2500'));
        $this->assertNull(Cache::store('redis')->tags($tags)->get('key:5000'));

        $keyCount = Cache::store('redis')->connection()->keys('*');
        $this->assertCount(0, $keyCount);
    }

    public function testFlushingManyTagsOnlyPassesEachEntrysOwnTagToTheScript()
    {
        $store = Cache::store('redis');
        $connection = $store->connection();

        if ($connection instanceof PhpRedisClusterConnection || $connection instanceof PredisClusterConnection) {
            $this->markTestSkipped('Clustered connections do not flush tags with a script.');
        }

        $store->clear();

        $prefix = $store->getPrefix();

        $store->tags(['people'])->put('person-1', 'Sally', 300);
        $store->tags(['people', 'artist'])->put('person-2', 'John', 300);
        $store->tags(['artist'])->forever('person-3', 'Taylor');
        $store->tags(['other'])->put('person-4', 'Abigail', 300);

        $calls = [];

        $connection->setEventDispatcher($this->app['events']);
        $connection->listen(function (CommandExecuted $event) use (&$calls) {
            if ($event->command === 'eval') {
                [$numberOfKeys, $arguments] = is_array($event->parameters[1])
                    ? [$event->parameters[2], $event->parameters[1]]
                    : [$event->parameters[1], array_slice($event->parameters, 2)];

                $calls[] = [
                    'keys' => array_map(fn ($key) => Str::afterLast($key, ':'), array_slice($arguments, 0, $numberOfKeys)),
                    'tags' => array_slice($arguments, $numberOfKeys + 1),
                ];
            }
        });

        $store->tags(['people', 'artist', 'empty-1', 'empty-2'])->flush();

        $connection->unsetEventDispatcher();

        $this->assertCount(2, $calls);
        $this->assertEqualsCanonicalizing(['person-1', 'person-2'], $calls[0]['keys']);
        $this->assertCount(1, $calls[0]['tags']);
        $this->assertStringEndsWith('tag:people:entries', $calls[0]['tags'][0]);
        $this->assertEqualsCanonicalizing(['person-2', 'person-3'], $calls[1]['keys']);
        $this->assertCount(1, $calls[1]['tags']);
        $this->assertStringEndsWith('tag:artist:entries', $calls[1]['tags'][0]);

        $this->assertNull($store->tags(['people'])->get('person-1'));
        $this->assertNull($store->tags(['people', 'artist'])->get('person-2'));
        $this->assertNull($store->tags(['artist'])->get('person-3'));
        $this->assertSame('Abigail', $store->tags(['other'])->get('person-4'));
        $this->assertSame(0, $connection->zcard($prefix.'tag:people:entries'));
        $this->assertSame(0, $connection->zcard($prefix.'tag:artist:entries'));
        $this->assertSame(1, $connection->zcard($prefix.'tag:other:entries'));
    }

    public function testFlushingTagsWhileASharedKeyIsRewrittenLeavesNoLiveValueUnindexed()
    {
        $store = Cache::store('redis');
        $connection = $store->connection();

        if ($connection instanceof PhpRedisClusterConnection || $connection instanceof PredisClusterConnection) {
            $this->markTestSkipped('Clustered connections do not flush tags with a script.');
        }

        $store->clear();

        $prefix = $store->getPrefix();

        $store->tags(['people', 'artist'])->forever('person', 'Sally');

        $rewritten = false;

        $connection->setEventDispatcher($this->app['events']);
        $connection->listen(function (CommandExecuted $event) use ($store, &$rewritten) {
            if ($event->command === 'eval' && ! $rewritten) {
                $rewritten = true;

                $store->tags(['people', 'artist'])->forever('person', 'John');
            }
        });

        $store->tags(['people', 'artist'])->flush();

        $connection->unsetEventDispatcher();

        $this->assertTrue($rewritten);

        $itemKey = $store->tags(['people', 'artist'])->taggedItemKey('person');

        if ($store->tags(['people', 'artist'])->get('person') !== null) {
            $this->assertContains($itemKey, $connection->zrange($prefix.'tag:people:entries', 0, -1));
            $this->assertContains($itemKey, $connection->zrange($prefix.'tag:artist:entries', 0, -1));
        }

        $store->tags(['people'])->flush();
        $store->tags(['artist'])->flush();

        $this->assertNull($store->tags(['people', 'artist'])->get('person'));
        $this->assertSame(0, $connection->zcard($prefix.'tag:people:entries'));
        $this->assertSame(0, $connection->zcard($prefix.'tag:artist:entries'));
    }

    public function testLocksCanBeFlushed()
    {
        /** @var \Illuminate\Cache\RedisStore $store */
        $store = Cache::store('redis');
        if (! $store->hasSeparateLockStore()) {
            $this->markTestSkipped('A separate Redis lock connection is required to test flushing locks.');
        }
        $store->flush();

        $store->lock('lock-1', 60)->acquire();
        $store->lock('lock-2', 60)->acquire();
        $store->lock('lock-3', 60)->acquire();

        $this->assertTrue($store->flushLocks());

        $this->assertTrue($store->lock('lock-1', 60)->acquire());
        $this->assertTrue($store->lock('lock-2', 60)->acquire());
        $this->assertTrue($store->lock('lock-3', 60)->acquire());
    }

    public function testFlushLocksDoesNotAffectNonLockKeys()
    {
        /** @var \Illuminate\Cache\RedisStore $store */
        $store = Cache::store('redis');
        if (! $store->hasSeparateLockStore()) {
            $this->markTestSkipped('A separate Redis lock connection is required to test flushing locks.');
        }
        $store->flush();

        $store->put('foo', 'bar', 60);
        $store->lock('lock-1', 60)->acquire();

        $store->flushLocks();

        $this->assertSame('bar', $store->get('foo'));
    }

    public function testHasSeparateLockStoreReturnsTrueWhenLockConnectionDiffers()
    {
        /** @var \Illuminate\Cache\RedisStore $store */
        $store = Cache::store('redis');
        if (! $store->hasSeparateLockStore()) {
            $this->markTestSkipped('A separate Redis lock connection is required to test flushing locks.');
        }

        $this->assertTrue($store->hasSeparateLockStore());
    }

    public function testHasSeparateLockStoreReturnsFalseWhenLockConnectionIsSame()
    {
        /** @var \Illuminate\Cache\RedisStore $store */
        $store = Cache::store('redis');
        $store->setConnection('default');
        $store->setLockConnection('default');

        $this->assertFalse($store->hasSeparateLockStore());
    }

    public function testFlushLocksThrowsExceptionWhenLockConnectionIsSame()
    {
        /** @var \Illuminate\Cache\RedisStore $store */
        $store = Cache::store('redis');
        $store->setConnection('default');
        $store->setLockConnection('default');

        $this->expectException(RuntimeException::class);

        $store->flushLocks();
    }
}

enum RedisTaggedCacheTestKey: string
{
    case PERSON_1 = 'person-1';
}
