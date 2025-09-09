<?php

namespace Illuminate\Tests\Cache;

use Illuminate\Cache\RateLimiter;
use Illuminate\Cache\RedisStore;
use Illuminate\Cache\Repository;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\Concerns\InteractsWithRedis;
use Illuminate\Redis\RedisManager;
use Illuminate\Support\Env;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Redis;

class RedisCacheIntegrationTest extends TestCase
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

    /**
     * @param  string  $driver
     */
    #[DataProvider('redisDriverProvider')]
    public function testRedisCacheAddTwice($driver)
    {
        $store = new RedisStore($this->redis[$driver]);
        $repository = new Repository($store);
        $this->assertTrue($repository->add('k', 'v', 3600));
        $this->assertFalse($repository->add('k', 'v', 3600));
        $this->assertGreaterThan(3500, $this->redis[$driver]->connection()->ttl('k'));
    }

    /**
     * @param  string  $driver
     */
    #[DataProvider('redisDriverProvider')]
    public function testRedisCacheRateLimiter($driver)
    {
        $store = new RedisStore($this->redis[$driver]);
        $repository = new Repository($store);
        $rateLimiter = new RateLimiter($repository);

        $this->assertFalse($rateLimiter->tooManyAttempts('key', 1));
        $this->assertEquals(1, $rateLimiter->hit('key', 60));
        $this->assertTrue($rateLimiter->tooManyAttempts('key', 1));
        $this->assertFalse($rateLimiter->tooManyAttempts('key', 2));
    }

    /**
     * Breaking change.
     *
     * @param  string  $driver
     */
    #[DataProvider('redisDriverProvider')]
    public function testRedisCacheAddFalse($driver)
    {
        $store = new RedisStore($this->redis[$driver]);
        $repository = new Repository($store);
        $repository->forever('k', false);
        $this->assertFalse($repository->add('k', 'v', 60));
        $this->assertEquals(-1, $this->redis[$driver]->connection()->ttl('k'));
    }

    /**
     * Breaking change.
     *
     * @param  string  $driver
     */
    #[DataProvider('redisDriverProvider')]
    public function testRedisCacheAddNull($driver)
    {
        $store = new RedisStore($this->redis[$driver]);
        $repository = new Repository($store);
        $repository->forever('k', null);
        $this->assertFalse($repository->add('k', 'v', 60));
    }

    #[DataProvider('phpRedisBackoffAlgorithmsProvider')]
    public function testPhpRedisBackoffAlgorithmParsing($friendlyAlgorithmName, $expectedAlgorithm)
    {
        $host = Env::get('REDIS_HOST', '127.0.0.1');
        $port = Env::get('REDIS_PORT', 6379);

        $manager = new RedisManager(new Application(), 'phpredis', [
            'default' => [
                'host' => $host,
                'port' => $port,
                'backoff_algorithm' => $friendlyAlgorithmName,
            ],
        ]);

        $this->assertEquals(
            $expectedAlgorithm,
            $manager->connection()->client()->getOption(Redis::OPT_BACKOFF_ALGORITHM)
        );
    }

    #[DataProvider('phpRedisBackoffAlgorithmsProvider')]
    public function testPhpRedisBackoffAlgorithm($friendlyAlgorithm, $expectedAlgorithm)
    {
        $host = Env::get('REDIS_HOST', '127.0.0.1');
        $port = Env::get('REDIS_PORT', 6379);

        $manager = new RedisManager(new Application(), 'phpredis', [
            'default' => [
                'host' => $host,
                'port' => $port,
                'backoff_algorithm' => $expectedAlgorithm,
            ],
        ]);

        $this->assertEquals(
            $expectedAlgorithm,
            $manager->connection()->client()->getOption(Redis::OPT_BACKOFF_ALGORITHM)
        );
    }

    public function testAnInvalidPhpRedisBackoffAlgorithmIsConvertedToDefault()
    {
        $host = Env::get('REDIS_HOST', '127.0.0.1');
        $port = Env::get('REDIS_PORT', 6379);

        $manager = new RedisManager(new Application(), 'phpredis', [
            'default' => [
                'host' => $host,
                'port' => $port,
                'backoff_algorithm' => 7,
            ],
        ]);

        $this->assertEquals(
            Redis::BACKOFF_ALGORITHM_DEFAULT,
            $manager->connection()->client()->getOption(Redis::OPT_BACKOFF_ALGORITHM)
        );
    }

    public function testItFailsWithAnInvalidPhpRedisAlgorithm()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Algorithm [foo] is not a valid PhpRedis backoff algorithm');

        $host = Env::get('REDIS_HOST', '127.0.0.1');
        $port = Env::get('REDIS_PORT', 6379);

        (new RedisManager(new Application(), 'phpredis', [
            'default' => [
                'host' => $host,
                'port' => $port,
                'backoff_algorithm' => 'foo',
            ],
        ]))->connection();
    }

    public static function phpRedisBackoffAlgorithmsProvider()
    {
        return [
            ['default', Redis::BACKOFF_ALGORITHM_DEFAULT],
            ['decorrelated_jitter', Redis::BACKOFF_ALGORITHM_DECORRELATED_JITTER],
            ['equal_jitter', Redis::BACKOFF_ALGORITHM_EQUAL_JITTER],
            ['exponential', Redis::BACKOFF_ALGORITHM_EXPONENTIAL],
            ['uniform', Redis::BACKOFF_ALGORITHM_UNIFORM],
            ['constant', Redis::BACKOFF_ALGORITHM_CONSTANT],
        ];
    }
}
