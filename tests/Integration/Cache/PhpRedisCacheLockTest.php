<?php

namespace Illuminate\Tests\Integration\Cache;

use Illuminate\Foundation\Testing\Concerns\InteractsWithRedis;
use Illuminate\Support\Facades\Cache;
use Orchestra\Testbench\TestCase;
use Redis;

/**
 * @group integration
 */
class PhpRedisCacheLockTest extends TestCase
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
    }

    public function testRedisLockCanBeAcquiredAndReleasedWithoutSerializationAndCompression()
    {
        $this->app['config']->set('database.redis.client', 'phpredis');
        $this->app['config']->set('cache.stores.redis.connection', 'default');
        $this->app['config']->set('cache.stores.redis.lock_connection', 'default');

        /** @var \Illuminate\Cache\RedisStore $store */
        $store = Cache::store('redis');
        /** @var \Redis $client */
        $client = $store->lockConnection()->client();

        $client->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_NONE);
        $store->lock('foo')->forceRelease();
        $this->assertNull($store->lockConnection()->get($store->getPrefix().'foo'));
        $lock = $store->lock('foo', 10);
        $this->assertTrue($lock->get());
        $this->assertFalse($store->lock('foo', 10)->get());
        $lock->release();
        $this->assertNull($store->lockConnection()->get($store->getPrefix().'foo'));
    }

    public function testRedisLockCanBeAcquiredAndReleasedWithPhpSerialization()
    {
        $this->app['config']->set('database.redis.client', 'phpredis');
        $this->app['config']->set('cache.stores.redis.connection', 'default');
        $this->app['config']->set('cache.stores.redis.lock_connection', 'default');

        /** @var \Illuminate\Cache\RedisStore $store */
        $store = Cache::store('redis');
        /** @var \Redis $client */
        $client = $store->lockConnection()->client();

        $client->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP);
        $store->lock('foo')->forceRelease();
        $this->assertNull($store->lockConnection()->get($store->getPrefix().'foo'));
        $lock = $store->lock('foo', 10);
        $this->assertTrue($lock->get());
        $this->assertFalse($store->lock('foo', 10)->get());
        $lock->release();
        $this->assertNull($store->lockConnection()->get($store->getPrefix().'foo'));
    }

    public function testRedisLockCanBeAcquiredAndReleasedWithJsonSerialization()
    {
        $this->app['config']->set('database.redis.client', 'phpredis');
        $this->app['config']->set('cache.stores.redis.connection', 'default');
        $this->app['config']->set('cache.stores.redis.lock_connection', 'default');

        /** @var \Illuminate\Cache\RedisStore $store */
        $store = Cache::store('redis');
        /** @var \Redis $client */
        $client = $store->lockConnection()->client();

        $client->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_JSON);
        $store->lock('foo')->forceRelease();
        $this->assertNull($store->lockConnection()->get($store->getPrefix().'foo'));
        $lock = $store->lock('foo', 10);
        $this->assertTrue($lock->get());
        $this->assertFalse($store->lock('foo', 10)->get());
        $lock->release();
        $this->assertNull($store->lockConnection()->get($store->getPrefix().'foo'));
    }

    public function testRedisLockCanBeAcquiredAndReleasedWithIgbinarySerialization()
    {
        if (! defined('Redis::SERIALIZER_IGBINARY')) {
            $this->markTestSkipped('Redis extension is not configured to support the igbinary serializer.');
        }

        $this->app['config']->set('database.redis.client', 'phpredis');
        $this->app['config']->set('cache.stores.redis.connection', 'default');
        $this->app['config']->set('cache.stores.redis.lock_connection', 'default');

        /** @var \Illuminate\Cache\RedisStore $store */
        $store = Cache::store('redis');
        /** @var \Redis $client */
        $client = $store->lockConnection()->client();

        $client->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_IGBINARY);
        $store->lock('foo')->forceRelease();
        $this->assertNull($store->lockConnection()->get($store->getPrefix().'foo'));
        $lock = $store->lock('foo', 10);
        $this->assertTrue($lock->get());
        $this->assertFalse($store->lock('foo', 10)->get());
        $lock->release();
        $this->assertNull($store->lockConnection()->get($store->getPrefix().'foo'));
    }

    public function testRedisLockCanBeAcquiredAndReleasedWithMsgpackSerialization()
    {
        if (! defined('Redis::SERIALIZER_MSGPACK')) {
            $this->markTestSkipped('Redis extension is not configured to support the msgpack serializer.');
        }

        $this->app['config']->set('database.redis.client', 'phpredis');
        $this->app['config']->set('cache.stores.redis.connection', 'default');
        $this->app['config']->set('cache.stores.redis.lock_connection', 'default');

        /** @var \Illuminate\Cache\RedisStore $store */
        $store = Cache::store('redis');
        /** @var \Redis $client */
        $client = $store->lockConnection()->client();

        $client->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_MSGPACK);
        $store->lock('foo')->forceRelease();
        $this->assertNull($store->lockConnection()->get($store->getPrefix().'foo'));
        $lock = $store->lock('foo', 10);
        $this->assertTrue($lock->get());
        $this->assertFalse($store->lock('foo', 10)->get());
        $lock->release();
        $this->assertNull($store->lockConnection()->get($store->getPrefix().'foo'));
    }

    public function testRedisLockCanBeAcquiredAndReleasedWithLzfCompression()
    {
        if (! defined('Redis::COMPRESSION_LZF')) {
            $this->markTestSkipped('Redis extension is not configured to support the lzf compression.');
        }

        if (! extension_loaded('lzf')) {
            $this->markTestSkipped('Lzf extension is not installed.');
        }

        $this->app['config']->set('database.redis.client', 'phpredis');
        $this->app['config']->set('cache.stores.redis.connection', 'default');
        $this->app['config']->set('cache.stores.redis.lock_connection', 'default');

        /** @var \Illuminate\Cache\RedisStore $store */
        $store = Cache::store('redis');
        /** @var \Redis $client */
        $client = $store->lockConnection()->client();

        $client->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_NONE);
        $client->setOption(Redis::OPT_COMPRESSION, Redis::COMPRESSION_LZF);
        $store->lock('foo')->forceRelease();
        $this->assertNull($store->lockConnection()->get($store->getPrefix().'foo'));
        $lock = $store->lock('foo', 10);
        $this->assertTrue($lock->get());
        $this->assertFalse($store->lock('foo', 10)->get());
        $lock->release();
        $this->assertNull($store->lockConnection()->get($store->getPrefix().'foo'));
    }

    public function testRedisLockCanBeAcquiredAndReleasedWithZstdCompression()
    {
        if (! defined('Redis::COMPRESSION_ZSTD')) {
            $this->markTestSkipped('Redis extension is not configured to support the zstd compression.');
        }

        if (! extension_loaded('zstd')) {
            $this->markTestSkipped('Zstd extension is not installed.');
        }

        $this->app['config']->set('database.redis.client', 'phpredis');
        $this->app['config']->set('cache.stores.redis.connection', 'default');
        $this->app['config']->set('cache.stores.redis.lock_connection', 'default');

        /** @var \Illuminate\Cache\RedisStore $store */
        $store = Cache::store('redis');
        /** @var \Redis $client */
        $client = $store->lockConnection()->client();

        $client->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_NONE);
        $client->setOption(Redis::OPT_COMPRESSION, Redis::COMPRESSION_ZSTD);
        $client->setOption(Redis::OPT_COMPRESSION_LEVEL, Redis::COMPRESSION_ZSTD_DEFAULT);
        $store->lock('foo')->forceRelease();
        $this->assertNull($store->lockConnection()->get($store->getPrefix().'foo'));
        $lock = $store->lock('foo', 10);
        $this->assertTrue($lock->get());
        $this->assertFalse($store->lock('foo', 10)->get());
        $lock->release();
        $this->assertNull($store->lockConnection()->get($store->getPrefix().'foo'));

        $client->setOption(Redis::OPT_COMPRESSION_LEVEL, Redis::COMPRESSION_ZSTD_MIN);
        $store->lock('foo')->forceRelease();
        $this->assertNull($store->lockConnection()->get($store->getPrefix().'foo'));
        $lock = $store->lock('foo', 10);
        $this->assertTrue($lock->get());
        $this->assertFalse($store->lock('foo', 10)->get());
        $lock->release();
        $this->assertNull($store->lockConnection()->get($store->getPrefix().'foo'));

        $client->setOption(Redis::OPT_COMPRESSION_LEVEL, Redis::COMPRESSION_ZSTD_MAX);
        $store->lock('foo')->forceRelease();
        $this->assertNull($store->lockConnection()->get($store->getPrefix().'foo'));
        $lock = $store->lock('foo', 10);
        $this->assertTrue($lock->get());
        $this->assertFalse($store->lock('foo', 10)->get());
        $lock->release();
        $this->assertNull($store->lockConnection()->get($store->getPrefix().'foo'));
    }

    public function testRedisLockCanBeAcquiredAndReleasedWithLz4Compression()
    {
        if (! defined('Redis::COMPRESSION_LZ4')) {
            $this->markTestSkipped('Redis extension is not configured to support the lz4 compression.');
        }

        if (! extension_loaded('lz4')) {
            $this->markTestSkipped('Lz4 extension is not installed.');
        }

        $this->markTestIncomplete(
            'phpredis extension does not compress consistently with the php '.
            'extension lz4. See: https://github.com/phpredis/phpredis/issues/1939'
        );

        $this->app['config']->set('database.redis.client', 'phpredis');
        $this->app['config']->set('cache.stores.redis.connection', 'default');
        $this->app['config']->set('cache.stores.redis.lock_connection', 'default');

        /** @var \Illuminate\Cache\RedisStore $store */
        $store = Cache::store('redis');
        /** @var \Redis $client */
        $client = $store->lockConnection()->client();

        $client->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_NONE);
        $client->setOption(Redis::OPT_COMPRESSION, Redis::COMPRESSION_LZ4);
        $client->setOption(Redis::OPT_COMPRESSION_LEVEL, 1);
        $store->lock('foo')->forceRelease();
        $this->assertNull($store->lockConnection()->get($store->getPrefix().'foo'));
        $lock = $store->lock('foo', 10);
        $this->assertTrue($lock->get());
        $this->assertFalse($store->lock('foo', 10)->get());
        $lock->release();
        $this->assertNull($store->lockConnection()->get($store->getPrefix().'foo'));

        $client->setOption(Redis::OPT_COMPRESSION_LEVEL, 3);
        $store->lock('foo')->forceRelease();
        $this->assertNull($store->lockConnection()->get($store->getPrefix().'foo'));
        $lock = $store->lock('foo', 10);
        $this->assertTrue($lock->get());
        $this->assertFalse($store->lock('foo', 10)->get());
        $lock->release();
        $this->assertNull($store->lockConnection()->get($store->getPrefix().'foo'));

        $client->setOption(Redis::OPT_COMPRESSION_LEVEL, 12);
        $store->lock('foo')->forceRelease();
        $this->assertNull($store->lockConnection()->get($store->getPrefix().'foo'));
        $lock = $store->lock('foo', 10);
        $this->assertTrue($lock->get());
        $this->assertFalse($store->lock('foo', 10)->get());
        $lock->release();
        $this->assertNull($store->lockConnection()->get($store->getPrefix().'foo'));
    }

    public function testRedisLockCanBeAcquiredAndReleasedWithSerializationAndCompression()
    {
        if (! defined('Redis::COMPRESSION_LZF')) {
            $this->markTestSkipped('Redis extension is not configured to support the lzf compression.');
        }

        if (! extension_loaded('lzf')) {
            $this->markTestSkipped('Lzf extension is not installed.');
        }

        $this->app['config']->set('database.redis.client', 'phpredis');
        $this->app['config']->set('cache.stores.redis.connection', 'default');
        $this->app['config']->set('cache.stores.redis.lock_connection', 'default');

        /** @var \Illuminate\Cache\RedisStore $store */
        $store = Cache::store('redis');
        /** @var \Redis $client */
        $client = $store->lockConnection()->client();

        $client->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_PHP);
        $client->setOption(Redis::OPT_COMPRESSION, Redis::COMPRESSION_LZF);
        $store->lock('foo')->forceRelease();
        $this->assertNull($store->lockConnection()->get($store->getPrefix().'foo'));
        $lock = $store->lock('foo', 10);
        $this->assertTrue($lock->get());
        $this->assertFalse($store->lock('foo', 10)->get());
        $lock->release();
        $this->assertNull($store->lockConnection()->get($store->getPrefix().'foo'));
    }
}
