<?php

namespace Illuminate\Tests\Integration\Cache;

use DateTime;
use Illuminate\Cache\RedisStore;
use Illuminate\Foundation\Testing\Concerns\InteractsWithRedis;
use Illuminate\Redis\Connections\PhpRedisClusterConnection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Sleep;
use Mockery as m;
use Orchestra\Testbench\TestCase;

class RedisStoreTest extends TestCase
{
    use InteractsWithRedis;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpRedis();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->tearDownRedis();
        m::close();
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

    public function testTagsCanBeAccessed()
    {
        Cache::store('redis')->clear();

        Cache::store('redis')->tags(['people', 'author'])->put('name', 'Sally', 5);
        Cache::store('redis')->tags(['people', 'author'])->put('age', 30, 5);

        $this->assertEquals('Sally', Cache::store('redis')->tags(['people', 'author'])->get('name'));
        $this->assertEquals(30, Cache::store('redis')->tags(['people', 'author'])->get('age'));

        Cache::store('redis')->tags(['people', 'author'])->flush();

        $keyCount = Cache::store('redis')->connection()->keys('*');
        $this->assertEquals(0, count($keyCount));
    }

    public function testTagEntriesCanBeStoredForever()
    {
        Cache::store('redis')->clear();

        Cache::store('redis')->tags(['people', 'author'])->forever('name', 'Sally');
        Cache::store('redis')->tags(['people', 'author'])->forever('age', 30);

        $this->assertEquals('Sally', Cache::store('redis')->tags(['people', 'author'])->get('name'));
        $this->assertEquals(30, Cache::store('redis')->tags(['people', 'author'])->get('age'));

        Cache::store('redis')->tags(['people', 'author'])->flush();

        $keyCount = Cache::store('redis')->connection()->keys('*');
        $this->assertEquals(0, count($keyCount));
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

    public function testIncrementedTagEntriesProperlyTurnStale()
    {
        Cache::store('redis')->clear();

        Cache::store('redis')->tags(['votes'])->add('person-1', 0, $seconds = 1);
        Cache::store('redis')->tags(['votes'])->increment('person-1');
        Cache::store('redis')->tags(['votes'])->increment('person-1');

        sleep(2);

        Cache::store('redis')->tags(['votes'])->flushStale();

        $keyCount = Cache::store('redis')->connection()->keys('*');
        $this->assertEquals(0, count($keyCount));
    }

    public function testPastTtlTagEntriesAreNotAdded()
    {
        Cache::store('redis')->clear();

        Cache::store('redis')->tags(['votes'])->add('person-1', 0, new DateTime('yesterday'));

        $value = Cache::store('redis')->tags(['votes'])->get('person-1');
        $this->assertNull($value);

        $keyCount = Cache::store('redis')->connection()->keys('*');
        $this->assertEquals(0, count($keyCount));
    }

    public function testPutPastTtlTagEntriesProperlyTurnStale()
    {
        Cache::store('redis')->clear();

        Cache::store('redis')->tags(['votes'])->put('person-1', 0, new DateTime('yesterday'));
        Cache::store('redis')->tags(['votes'])->flushStale();

        $keyCount = Cache::store('redis')->connection()->keys('*');
        $this->assertEquals(0, count($keyCount));
    }

    public function testTagsCanBeFlushedBySingleKey()
    {
        Cache::store('redis')->clear();

        Cache::store('redis')->tags(['people', 'author'])->put('person-1', 'Sally', 5);
        Cache::store('redis')->tags(['people', 'artist'])->put('person-2', 'John', 5);

        Cache::store('redis')->tags(['artist'])->flush();

        $this->assertEquals('Sally', Cache::store('redis')->tags(['people', 'author'])->get('person-1'));
        $this->assertNull(Cache::store('redis')->tags(['people', 'artist'])->get('person-2'));

        $keyCount = Cache::store('redis')->connection()->keys('*');
        $this->assertEquals(3, count($keyCount)); // Sets for people, authors, and actual entry for Sally
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
        $this->assertEquals(4, count($keyCount)); // Sets for people, authors, and artists + individual entry for Jennifer
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

        $this->assertEquals([], $store->many([]));
    }

    public function testPutManyCallsPutWhenClustered()
    {
        $store = m::mock(RedisStore::class)->makePartial();
        $store->expects('connection')->andReturn(m::mock(PhpRedisClusterConnection::class));
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
}
