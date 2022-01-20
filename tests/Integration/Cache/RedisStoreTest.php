<?php

namespace Illuminate\Tests\Integration\Cache;

use Illuminate\Cache\RedisStore;
use Illuminate\Foundation\Testing\Concerns\InteractsWithRedis;
use Illuminate\Redis\Connections\PhpRedisConnection;
use Orchestra\Testbench\TestCase;

class RedisStoreTest extends TestCase
{
    use InteractsWithRedis;

    protected function tearDown(): void
    {
        $this->tearDownRedis();

        parent::tearDown();
    }

    /**
     * @dataProvider extendedRedisConnectionDataProvider
     */
    public function testItCanStoreInfinite($connection)
    {
        $repository = $this->getRepository($connection);
        /** @var \Illuminate\Cache\RedisStore $redisStore */
        $redisStore = $repository->getStore();
        $redisConnection = $redisStore->connection();

        if ($redisConnection instanceof PhpRedisConnection && $redisConnection->jsonSerialized()) {
            $this->markTestSkipped(
                'JSON does not support INF or -INF. It will be serialized to null '.
                'and as a result phpredis will store it as 0.'
            );
        }

        $result = $repository->put('foo', INF);
        $this->assertTrue($result);
        $this->assertSame(INF, $repository->get('foo'));

        $result = $repository->put('foo', -INF);
        $this->assertTrue($result);
        $this->assertSame(-INF, $repository->get('foo'));
    }

    /**
     * @dataProvider extendedRedisConnectionDataProvider
     */
    public function testItCanStoreNan($connection)
    {
        $repository = $this->getRepository($connection);
        /** @var \Illuminate\Cache\RedisStore $redisStore */
        $redisStore = $repository->getStore();
        $redisConnection = $redisStore->connection();

        if ($redisConnection instanceof PhpRedisConnection && $redisConnection->jsonSerialized()) {
            $this->markTestSkipped(
                'JSON does not support NAN. It will be serialized to null '.
                'and as a result phpredis will store it as 0.'
            );
        }

        $result = $repository->put('foo', NAN);
        $this->assertTrue($result);
        $this->assertNan($repository->get('foo'));
    }

    /**
     * @dataProvider extendedRedisConnectionDataProvider
     */
    public function testItCanAdd($connection)
    {
        $repository = $this->getRepository($connection);

        $result = $repository->add('foo', 'test test test');
        $this->assertTrue($result);
        $this->assertSame('test test test', $repository->get('foo'));
        $result = $repository->forget('foo');
        $this->assertTrue($result);
    }

    /**
     * @dataProvider extendedRedisConnectionDataProvider
     */
    public function testItCanAddWithTtl($connection)
    {
        $this->markTestIncomplete('Needs support in the RedisStore first.');

        $repository = $this->getRepository($connection);

        $result = $repository->add('foo', 'test test test', 10);
        $this->assertTrue($result);
        $this->assertSame('test test test', $repository->get('foo'));
        $result = $repository->forget('foo');
        $this->assertTrue($result);
    }

    /**
     * @dataProvider extendedRedisConnectionDataProvider
     */
    public function testItCanPut($connection)
    {
        $repository = $this->getRepository($connection);

        $result = $repository->put('foo', 'test test test');
        $this->assertTrue($result);
        $this->assertSame('test test test', $repository->get('foo'));
        $result = $repository->forget('foo');
        $this->assertTrue($result);
    }

    /**
     * @dataProvider extendedRedisConnectionDataProvider
     */
    public function testItCanPutWithTtl($connection)
    {
        $repository = $this->getRepository($connection);

        $result = $repository->put('foo', 'test test test', 10);
        $this->assertTrue($result);
        $this->assertSame('test test test', $repository->get('foo'));
        $result = $repository->forget('foo');
        $this->assertTrue($result);
    }

    /**
     * @dataProvider extendedRedisConnectionDataProvider
     */
    public function testItCanPutMany($connection)
    {
        $repository = $this->getRepository($connection);

        $result = $repository->put([
            'foo1' => 'test test test',
            'foo2' => 'best best best',
        ], null);
        $this->assertTrue($result);
        $this->assertSame('test test test', $repository->get('foo1'));
        $result = $repository->forget('foo1');
        $this->assertSame('best best best', $repository->get('foo2'));
        $result = $repository->forget('foo2');
        $this->assertTrue($result);
    }

    /**
     * @dataProvider extendedRedisConnectionDataProvider
     */
    public function testItCanPutManyWithTtl($connection)
    {
        $repository = $this->getRepository($connection);

        $result = $repository->put([
            'foo1' => 'test test test',
            'foo2' => 'best best best',
        ], 10);
        $this->assertTrue($result);
        $this->assertSame('test test test', $repository->get('foo1'));
        $result = $repository->forget('foo1');
        $this->assertSame('best best best', $repository->get('foo2'));
        $result = $repository->forget('foo2');
        $this->assertTrue($result);
    }

    /**
     * @dataProvider extendedRedisConnectionDataProvider
     */
    public function testItCanGetMany($connection)
    {
        $repository = $this->getRepository($connection);

        $result = $repository->put([
            'foo1' => 'test test test',
            'foo2' => 'best best best',
            'foo3' => 'this is the best test',
        ], null);
        $this->assertTrue($result);
        $result = $repository->getMultiple(['foo1', 'foo2', 'foo3', 'foo4'], 'sure?');
        $this->assertIsArray($result);
        $this->assertArrayHasKey('foo1', $result);
        $this->assertArrayHasKey('foo2', $result);
        $this->assertArrayHasKey('foo3', $result);
        $this->assertArrayHasKey('foo4', $result);
        $this->assertSame('test test test', $result['foo1']);
        $this->assertSame('best best best', $result['foo2']);
        $this->assertSame('this is the best test', $result['foo3']);
        $this->assertSame('sure?', $result['foo4']);
        $result = $repository->deleteMultiple(['foo1', 'foo2', 'foo3']);
        $this->assertTrue($result);
    }

    /**
     * Builds a cache repository out of a predefined redis connection name.
     *
     * @param  string  $connection
     * @return \Illuminate\Cache\Repository
     */
    private function getRepository($connection)
    {
        /** @var \Illuminate\Cache\CacheManager $cacheManager */
        $cacheManager = $this->app->get('cache');

        return $cacheManager->repository(new RedisStore($this->getRedisManager($connection)));
    }
}
