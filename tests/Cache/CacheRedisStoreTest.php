<?php

namespace Illuminate\Tests\Cache;

use Illuminate\Cache\RedisStore;
use Illuminate\Contracts\Redis\Factory;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class CacheRedisStoreTest extends TestCase
{
    public function testGetReturnsNullWhenNotFound()
    {
        $redis = $this->getRedis();
        $redis->getRedis()->expects('connection')->with('default')->andReturn($redis->getRedis());
        $redis->getRedis()->expects('get')->with('prefix:foo')->andReturn(null);
        $this->assertNull($redis->get('foo'));
    }

    public function testRedisValueIsReturned()
    {
        $redis = $this->getRedis();
        $redis->getRedis()->expects('connection')->with('default')->andReturn($redis->getRedis());
        $redis->getRedis()->expects('get')->with('prefix:foo')->andReturn(serialize('foo'));
        $this->assertSame('foo', $redis->get('foo'));
    }

    public function testRedisMultipleValuesAreReturned()
    {
        $redis = $this->getRedis();
        $redis->getRedis()->expects('connection')->with('default')->andReturn($redis->getRedis());
        $redis->getRedis()->expects('mget')->with(['prefix:foo', 'prefix:fizz', 'prefix:norf', 'prefix:null'])
            ->andReturn([
                serialize('bar'),
                serialize('buzz'),
                serialize('quz'),
                null,
            ]);

        $results = $redis->many(['foo', 'fizz', 'norf', 'null']);

        $this->assertSame('bar', $results['foo']);
        $this->assertSame('buzz', $results['fizz']);
        $this->assertSame('quz', $results['norf']);
        $this->assertNull($results['null']);
    }

    public function testRedisValueIsReturnedForNumerics()
    {
        $redis = $this->getRedis();
        $redis->getRedis()->expects('connection')->with('default')->andReturn($redis->getRedis());
        $redis->getRedis()->expects('get')->with('prefix:foo')->andReturn(1);
        $this->assertEquals(1, $redis->get('foo'));
    }

    public function testSetMethodProperlyCallsRedis()
    {
        $redis = $this->getRedis();
        $redis->getRedis()->expects('connection')->with('default')->andReturn($redis->getRedis());
        $redis->getRedis()->expects('setex')->with('prefix:foo', 60, serialize('foo'))->andReturn('OK');
        $result = $redis->put('foo', 'foo', 60);
        $this->assertTrue($result);
    }

    public function testSetMultipleMethodProperlyCallsRedis()
    {
        $redis = $this->getRedis();
        /** @var m\MockInterface $connection */
        $connection = $redis->getRedis();
        $connection->expects('connection')->with('default')->andReturn($redis->getRedis());
        $connection->expects('multi');
        $redis->getRedis()->expects('setex')->with('prefix:foo', 60, serialize('bar'))->andReturn('OK');
        $redis->getRedis()->expects('setex')->with('prefix:baz', 60, serialize('qux'))->andReturn('OK');
        $redis->getRedis()->expects('setex')->with('prefix:bar', 60, serialize('norf'))->andReturn('OK');
        $connection->expects('exec');

        $result = $redis->putMany([
            'foo' => 'bar',
            'baz' => 'qux',
            'bar' => 'norf',
        ], 60);
        $this->assertTrue($result);
    }

    public function testSetMethodProperlyCallsRedisForNumerics()
    {
        $redis = $this->getRedis();
        $redis->getRedis()->expects('connection')->with('default')->andReturn($redis->getRedis());
        $redis->getRedis()->expects('setex')->with('prefix:foo', 60, 1);
        $result = $redis->put('foo', 1, 60);
        $this->assertFalse($result);
    }

    public function testIncrementMethodProperlyCallsRedis()
    {
        $redis = $this->getRedis();
        $redis->getRedis()->expects('connection')->with('default')->andReturn($redis->getRedis());
        $redis->getRedis()->expects('incrby')->with('prefix:foo', 5);
        $redis->increment('foo', 5);
    }

    public function testDecrementMethodProperlyCallsRedis()
    {
        $redis = $this->getRedis();
        $redis->getRedis()->expects('connection')->with('default')->andReturn($redis->getRedis());
        $redis->getRedis()->expects('decrby')->with('prefix:foo', 5);
        $redis->decrement('foo', 5);
    }

    public function testStoreItemForeverProperlyCallsRedis()
    {
        $redis = $this->getRedis();
        $redis->getRedis()->expects('connection')->with('default')->andReturn($redis->getRedis());
        $redis->getRedis()->expects('set')->with('prefix:foo', serialize('foo'))->andReturn('OK');
        $result = $redis->forever('foo', 'foo', 60);
        $this->assertTrue($result);
    }

    public function testTouchMethodProperlyCallsRedis(): void
    {
        $key = 'key';
        $ttl = 60;

        $redis = $this->getRedis();

        $redis->getRedis()->expects('connection')->with('default')->andReturn($redis->getRedis());
        $redis->getRedis()->expects('expire')->with("prefix:$key", $ttl)->andReturn(true);

        $this->assertTrue($redis->touch($key, $ttl));
    }

    public function testForgetMethodProperlyCallsRedis()
    {
        $redis = $this->getRedis();
        $redis->getRedis()->expects('connection')->with('default')->andReturn($redis->getRedis());
        $redis->getRedis()->expects('del')->with('prefix:foo');
        $redis->forget('foo');
    }

    public function testFlushesCached()
    {
        $redis = $this->getRedis();
        $redis->getRedis()->expects('connection')->with('default')->andReturn($redis->getRedis());
        $redis->getRedis()->expects('flushdb')->andReturn('ok');
        $result = $redis->flush();
        $this->assertTrue($result);
    }

    public function testFlushesCachedLocks()
    {
        $redis = $this->getRedis();
        $redis->getRedis()->expects('connection')->with('locks')->andReturn($redis->getRedis());
        $redis->getRedis()->expects('flushdb')->andReturn('ok');
        $redis->setLockConnection('locks');
        $result = $redis->flushLocks();
        $this->assertTrue($result);
    }

    public function testGetAndSetPrefix()
    {
        $redis = $this->getRedis();
        $this->assertSame('prefix:', $redis->getPrefix());
        $redis->setPrefix('foo');
        $this->assertSame('foo', $redis->getPrefix());
        $redis->setPrefix(null);
        $this->assertEmpty($redis->getPrefix());
    }

    protected function getRedis()
    {
        return new RedisStore(m::mock(Factory::class), 'prefix:');
    }
}
