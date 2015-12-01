<?php

use Mockery as m;

class CacheRedisStoreTest extends PHPUnit_Framework_TestCase
{
    public function tearDown()
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
        $this->assertEquals('foo', $redis->get('foo'));
    }

    public function testRedisMultipleValuesAreReturned()
    {
        $redis = $this->getRedis();
        $redis->getRedis()->shouldReceive('connection')->once()->with('default')->andReturn($redis->getRedis());
        $redis->getRedis()->shouldReceive('mget')->once()->with(['prefix:foo', 'prefix:fizz', 'prefix:norf'])
            ->andReturn([
                serialize('bar'),
                serialize('buzz'),
                serialize('quz'),
            ]);
        $this->assertEquals([
            'foo'   => 'bar',
            'fizz'  => 'buzz',
            'norf'  => 'quz',
        ], $redis->getMultiple([
            'foo', 'fizz', 'norf',
        ]));
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
        $redis->getRedis()->shouldReceive('setex')->once()->with('prefix:foo', 60 * 60, serialize('foo'));
        $redis->put('foo', 'foo', 60);
    }

    public function testSetMultipleMethodProperlyCallsRedis()
    {
        $redis = $this->getRedis();
        /** @var m\MockInterface $connection */
        $connection = $redis->getRedis();
        $connection->shouldReceive('connection')->with('default')->andReturn($redis->getRedis());
        $connection->shouldReceive('multi')->once();
        $redis->getRedis()->shouldReceive('setex')->with('prefix:foo', 60 * 60, serialize('bar'));
        $redis->getRedis()->shouldReceive('setex')->with('prefix:baz', 60 * 60, serialize('qux'));
        $redis->getRedis()->shouldReceive('setex')->with('prefix:bar', 60 * 60, serialize('norf'));
        $connection->shouldReceive('exec')->once();

        $redis->putMultiple([
            'foo'   => 'bar',
            'baz'   => 'qux',
            'bar' => 'norf',
        ], 60);
    }

    public function testSetMethodProperlyCallsRedisForNumerics()
    {
        $redis = $this->getRedis();
        $redis->getRedis()->shouldReceive('connection')->once()->with('default')->andReturn($redis->getRedis());
        $redis->getRedis()->shouldReceive('setex')->once()->with('prefix:foo', 60 * 60, 1);
        $redis->put('foo', 1, 60);
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
        $redis->getRedis()->shouldReceive('set')->once()->with('prefix:foo', serialize('foo'));
        $redis->forever('foo', 'foo', 60);
    }

    public function testForgetMethodProperlyCallsRedis()
    {
        $redis = $this->getRedis();
        $redis->getRedis()->shouldReceive('connection')->once()->with('default')->andReturn($redis->getRedis());
        $redis->getRedis()->shouldReceive('del')->once()->with('prefix:foo');
        $redis->forget('foo');
    }

    public function testGetAndSetPrefix()
    {
        $redis = $this->getRedis();
        $this->assertEquals('prefix:', $redis->getPrefix());
        $redis->setPrefix('foo');
        $this->assertEquals('foo:', $redis->getPrefix());
        $redis->setPrefix(null);
        $this->assertEmpty($redis->getPrefix());
    }

    protected function getRedis()
    {
        return new Illuminate\Cache\RedisStore(m::mock('Illuminate\Redis\Database'), 'prefix');
    }
}
