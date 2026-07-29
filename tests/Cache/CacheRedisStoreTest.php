<?php

namespace Illuminate\Tests\Cache;

use Illuminate\Cache\RedisStore;
use Illuminate\Contracts\Redis\Factory;
use Illuminate\Redis\Connections\PhpRedisClusterConnection;
use Illuminate\Redis\Connections\PredisConnection;
use Mockery as m;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;
use RuntimeException;

class CacheRedisStoreTest extends TestCase
{
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

    public function testTouchMethodProperlyCallsRedis(): void
    {
        $key = 'key';
        $ttl = 60;

        $redis = $this->getRedis();

        $redis->getRedis()->shouldReceive('connection')->once()->with('default')->andReturn($redis->getRedis());
        $redis->getRedis()->shouldReceive('expire')->once()->with("prefix:$key", $ttl)->andReturn(true);

        $this->assertTrue($redis->touch($key, $ttl));
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

    public function testFlushesCachedByPrefix()
    {
        $redis = $this->getRedis();
        $redis->getRedis()->shouldReceive('connection')->once()->with('default')->andReturn($redis->getRedis());
        $redis->getRedis()->shouldReceive('scan')->once()->with('0', ['match' => 'prefix:*', 'count' => 1000])->andReturn(['17', ['prefix:foo', 'prefix:bar']]);
        $redis->getRedis()->shouldReceive('del')->once()->with('prefix:foo', 'prefix:bar');
        $redis->getRedis()->shouldReceive('scan')->once()->with('17', ['match' => 'prefix:*', 'count' => 1000])->andReturn(['0', ['prefix:baz']]);
        $redis->getRedis()->shouldReceive('del')->once()->with('prefix:baz');

        $result = $redis->flushPrefix();

        $this->assertTrue($result);
    }

    public function testFlushesCachedByPrefixWithRedisConnectionPrefix()
    {
        $factory = m::mock(Factory::class);
        $connection = new CacheRedisStorePredisConnectionStub(new CacheRedisStorePredisClientStub('redis:'));

        $factory->shouldReceive('connection')->once()->with('default')->andReturn($connection);

        $result = (new RedisStore($factory, 'prefix:'))->flushPrefix();

        $this->assertTrue($result);
        $this->assertSame([
            ['0', ['match' => 'redis:prefix:*', 'count' => 1000]],
        ], $connection->scans);
        $this->assertSame([
            ['prefix:foo', 'prefix:bar'],
        ], $connection->deletions);
    }

    public function testFlushPrefixRequiresCachePrefix()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Flushing Redis cache by prefix is only supported when a cache prefix is configured.');

        (new RedisStore(m::mock(Factory::class), ''))->flushPrefix();
    }

    #[RequiresPhpExtension('redis')]
    public function testFlushesCachedByPrefixAcrossPhpRedisClusterMasters()
    {
        $client = m::mock(\RedisCluster::class);
        $factory = m::mock(Factory::class);
        $connection = new PhpRedisClusterConnection($client);

        $client->shouldReceive('getOption')->once()->with(\Redis::OPT_PREFIX)->andReturn('');
        $client->shouldReceive('_masters')->once()->andReturn([
            ['127.0.0.1', '6379'],
            ['127.0.0.2', '6379'],
        ]);
        $client->shouldReceive('scan')->once()->with(m::any(), ['127.0.0.1', '6379'], 'prefix:*', 1000)->andReturn(['prefix:foo']);
        $client->shouldReceive('scan')->once()->with(m::any(), ['127.0.0.2', '6379'], 'prefix:*', 1000)->andReturn(['prefix:bar']);
        $client->shouldReceive('del')->once()->with('prefix:foo')->andReturn(1);
        $client->shouldReceive('del')->once()->with('prefix:bar')->andReturn(1);

        $factory->shouldReceive('connection')->once()->with('default')->andReturn($connection);

        $result = (new RedisStore($factory, 'prefix:'))->flushPrefix();

        $this->assertTrue($result);
    }

    public function testFlushesCachedLocks()
    {
        $redis = $this->getRedis();
        $redis->getRedis()->shouldReceive('connection')->once()->with('locks')->andReturn($redis->getRedis());
        $redis->getRedis()->shouldReceive('flushdb')->once()->andReturn('ok');
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

class CacheRedisStorePredisConnectionStub extends PredisConnection
{
    public array $deletions = [];

    public array $scans = [];

    public function scan($cursor, $options = [])
    {
        $this->scans[] = [$cursor, $options];

        return ['0', ['redis:prefix:foo', 'redis:prefix:bar']];
    }

    public function del(...$keys)
    {
        $this->deletions[] = $keys;

        return count($keys);
    }
}

class CacheRedisStorePredisClientStub
{
    public function __construct(protected string $prefix)
    {
    }

    public function getOptions()
    {
        return (object) ['prefix' => $this->prefix];
    }
}
