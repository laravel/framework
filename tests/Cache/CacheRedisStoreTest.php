<?php

namespace Illuminate\Tests\Cache;

use Illuminate\Cache\RedisStore;
use Illuminate\Contracts\Redis\Factory;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class CacheRedisStoreTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testGetReturnsNullWhenNotFound()
    {
        $redis = $this->getRedis();
        $redis->getRedis()->shouldReceive('connection')->once()->with('default')->andReturn($redis->getRedis());
        $redis->getRedis()->shouldReceive('get')->once()->with('prefix:foo')->andReturn(null);
        $this->assertNull($redis->get('foo'));
    }

    public function testRedisValueIsReturned()
    {
        $redis = $this->getRedis();
        $redis->getRedis()->shouldReceive('connection')->once()->with('default')->andReturn($redis->getRedis());
        $redis->getRedis()->shouldReceive('get')->once()->with('prefix:foo')->andReturn(serialize('foo'));
        $this->assertSame('foo', $redis->get('foo'));
    }

    public function testRedisMultipleValuesAreReturned()
    {
        $redis = $this->getRedis();
        $redis->getRedis()->shouldReceive('connection')->once()->with('default')->andReturn($redis->getRedis());
        $redis->getRedis()->shouldReceive('mget')->once()->with(['prefix:foo', 'prefix:fizz', 'prefix:norf', 'prefix:null'])
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
        $redis->getRedis()->shouldReceive('connection')->once()->with('default')->andReturn($redis->getRedis());
        $redis->getRedis()->shouldReceive('get')->once()->with('prefix:foo')->andReturn(1);
        $this->assertEquals(1, $redis->get('foo'));
    }

    public function testSetMethodProperlyCallsRedis()
    {
        $redis = $this->getRedis();
        $redis->getRedis()->shouldReceive('connection')->once()->with('default')->andReturn($redis->getRedis());
        $redis->getRedis()->shouldReceive('setex')->once()->with('prefix:foo', 60, serialize('foo'))->andReturn('OK');
        $result = $redis->put('foo', 'foo', 60);
        $this->assertTrue($result);
    }

    public function testSetMultipleMethodProperlyCallsRedis()
    {
        $redis = $this->getRedis();
        /** @var m\MockInterface $connection */
        $connection = $redis->getRedis();
        $connection->shouldReceive('connection')->with('default')->andReturn($redis->getRedis());
        $connection->shouldReceive('multi')->once();
        $redis->getRedis()->shouldReceive('setex')->once()->with('prefix:foo', 60, serialize('bar'))->andReturn('OK');
        $redis->getRedis()->shouldReceive('setex')->once()->with('prefix:baz', 60, serialize('qux'))->andReturn('OK');
        $redis->getRedis()->shouldReceive('setex')->once()->with('prefix:bar', 60, serialize('norf'))->andReturn('OK');
        $connection->shouldReceive('exec')->once();

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
        $redis->getRedis()->shouldReceive('connection')->once()->with('default')->andReturn($redis->getRedis());
        $redis->getRedis()->shouldReceive('setex')->once()->with('prefix:foo', 60, 1);
        $result = $redis->put('foo', 1, 60);
        $this->assertFalse($result);
    }

    public function testIncrementMethodProperlyCallsRedis()
    {
        $redis = $this->getRedis();
        $redis->getRedis()->shouldReceive('connection')->once()->with('default')->andReturn($redis->getRedis());
        $redis->getRedis()->shouldReceive('incrby')->once()->with('prefix:foo', 5);
        $redis->increment('foo', 5);
    }

    public function testDecrementMethodProperlyCallsRedis()
    {
        $redis = $this->getRedis();
        $redis->getRedis()->shouldReceive('connection')->once()->with('default')->andReturn($redis->getRedis());
        $redis->getRedis()->shouldReceive('decrby')->once()->with('prefix:foo', 5);
        $redis->decrement('foo', 5);
    }

    public function testStoreItemForeverProperlyCallsRedis()
    {
        $redis = $this->getRedis();
        $redis->getRedis()->shouldReceive('connection')->once()->with('default')->andReturn($redis->getRedis());
        $redis->getRedis()->shouldReceive('set')->once()->with('prefix:foo', serialize('foo'))->andReturn('OK');
        $result = $redis->forever('foo', 'foo', 60);
        $this->assertTrue($result);
    }

    public function testForgetMethodProperlyCallsRedis()
    {
        $redis = $this->getRedis();
        $redis->getRedis()->shouldReceive('connection')->once()->with('default')->andReturn($redis->getRedis());
        $redis->getRedis()->shouldReceive('del')->once()->with('prefix:foo');
        $redis->forget('foo');
    }

    public function testFlushesCached()
    {
        $redis = $this->getRedis();
        $redis->getRedis()->shouldReceive('connection')->once()->with('default')->andReturn($redis->getRedis());
        $redis->getRedis()->shouldReceive('flushdb')->once()->andReturn('ok');
        $result = $redis->flush();
        $this->assertTrue($result);
    }

    public function testGetAndSetPrefix()
    {
        $redis = $this->getRedis();
        $this->assertSame('prefix:', $redis->getPrefix());
        $redis->setPrefix('foo');
        $this->assertSame('foo:', $redis->getPrefix());
        $redis->setPrefix(null);
        $this->assertEmpty($redis->getPrefix());
    }

    protected function getRedis()
    {
        return new RedisStore(m::mock(Factory::class), 'prefix');
    }
}
